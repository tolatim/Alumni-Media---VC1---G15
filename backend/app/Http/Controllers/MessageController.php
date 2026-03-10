<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function unreadCount(Request $request)
    {
        $me = $request->user();

        $count = Message::query()
            ->where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'message' => 'Unread count fetched successfully',
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    public function contacts(Request $request)
    {
        $me = $request->user();
        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        $connections = Connection::query()
            ->with(['requester', 'addressee'])
            ->where('status', 'accepted')
            ->where(function ($query) use ($me) {
                $query->where('requester_id', $me->id)
                    ->orWhere('addressee_id', $me->id);
            })
            ->latest()
            ->paginate($perPage);

        $contacts = collect($connections->items())->map(function ($row) use ($me) {
            return $row->requester_id === $me->id ? $row->addressee : $row->requester;
        })->filter()->values();

        $unreadMap = Message::query()
            ->selectRaw('sender_id, COUNT(*) as unread_count')
            ->where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->groupBy('sender_id')
            ->pluck('unread_count', 'sender_id');

        $contacts = $contacts->map(function ($contact) use ($unreadMap) {
            $contact->unread_count = (int) ($unreadMap[$contact->id] ?? 0);
            return $contact;
        });

        return response()->json([
            'message' => 'Contacts fetched successfully',
            'data' => $contacts,
            'pagination' => [
                'current_page' => $connections->currentPage(),
                'last_page' => $connections->lastPage(),
                'per_page' => $connections->perPage(),
                'total' => $connections->total(),
            ],
        ]);
    }

    public function index(Request $request, $userId)
    {
        $me = $request->user();
        $targetId = (int) $userId;
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $this->assertCanMessage($me->id, $targetId);

        $messages = Message::query()
            ->where(function ($query) use ($me, $targetId) {
                $query->where('sender_id', $me->id)
                    ->where('receiver_id', $targetId);
            })
            ->orWhere(function ($query) use ($me, $targetId) {
                $query->where('sender_id', $targetId)
                    ->where('receiver_id', $me->id);
            })
            ->with(['sender', 'receiver'])
            ->latest()
            ->paginate($perPage);

        $items = collect($messages->items())
            ->reverse()
            ->values();

        return response()->json([
            'message' => 'Messages fetched successfully',
            'data' => $items,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function store(Request $request, $userId)
    {
        $me = $request->user();
        $targetId = (int) $userId;

        $this->assertCanMessage($me->id, $targetId);

        $validated = $request->validate([
            'content' => 'nullable|string|max:5000',
            'media_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi,webm|max:20480',
        ]);

        if (empty($validated['content']) && !$request->hasFile('media_file')) {
            return response()->json([
                'message' => 'Message content or media is required.',
            ], 422);
        }

        $mediaPath = null;
        $mediaType = null;

        if ($request->hasFile('media_file')) {
            $mime = $request->file('media_file')->getMimeType() ?: '';
            $mediaType = str_starts_with($mime, 'video/') ? 'video' : 'image';
            $dir = $mediaType === 'video' ? 'messages/videos' : 'messages/images';
            $mediaPath = $request->file('media_file')->store($dir, 'public');
        }

        $message = Message::create([
            'sender_id' => $me->id,
            'receiver_id' => $targetId,
            'content' => $validated['content'] ?? null,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'status' => 'sent',
        ])->load(['sender', 'receiver']);

        Notification::create([
            'user_id' => $targetId,
            'notifiable_id' => $message->id,
            'notifiable_type' => Message::class,
            'type' => 'new_message',
            'data' => [
                'message' => ($me->first_name ?: 'Someone') . ' sent you a message.',
                'link' => '/message',
            ],
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message,
        ], 201);
    }

    public function markRead(Request $request, $userId)
    {
        $me = $request->user();
        $targetId = (int) $userId;

        $this->assertCanMessage($me->id, $targetId);

        Message::query()
            ->where('sender_id', $targetId)
            ->where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);

        return response()->json([
            'message' => 'Messages marked as read successfully',
        ]);
    }

    public function destroy(Request $request, $messageId)
    {
        $me = $request->user();

        $message = Message::query()->find($messageId);

        if (!$message) {
            return response()->json([
                'message' => 'Message not found.',
            ], 404);
        }

        if ((int) $message->sender_id !== (int) $me->id) {
            return response()->json([
                'message' => 'You can only delete your own messages.',
            ], 403);
        }

        if (!empty($message->media_path)) {
            $path = $message->media_path;
            if (Str::startsWith($path, '/storage/')) {
                $path = Str::replaceFirst('/storage/', '', $path);
            }

            if (!Str::startsWith($path, ['http://', 'https://']) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $message->delete();

        return response()->json([
            'message' => 'Message deleted successfully',
        ]);
    }

    public function update(Request $request, $messageId)
    {
        $me = $request->user();

        $message = Message::query()->find($messageId);

        if (!$message) {
            return response()->json([
                'message' => 'Message not found.',
            ], 404);
        }

        if ((int) $message->sender_id !== (int) $me->id) {
            return response()->json([
                'message' => 'You can only edit your own messages.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'nullable|string|max:5000',
        ]);

        $content = isset($validated['content']) ? trim((string) $validated['content']) : null;

        if ($content === '' || $content === null) {
            if (!$message->media_path) {
                return response()->json([
                    'message' => 'Message content is required.',
                ], 422);
            }
            $content = null;
        }

        $message->update([
            'content' => $content,
            'status' => 'sent',
        ]);

        return response()->json([
            'message' => 'Message updated successfully',
            'data' => $message->fresh()->load(['sender', 'receiver']),
        ]);
    }

    private function assertCanMessage(int $meId, int $targetId): void
    {
        if ($meId === $targetId) {
            abort(response()->json([
                'message' => 'You cannot message yourself.',
            ], 422));
        }

        if (!User::query()->where('id', $targetId)->exists()) {
            abort(response()->json([
                'message' => 'User not found.',
            ], 404));
        }

        $connection = Connection::query()
            ->where(function ($query) use ($meId, $targetId) {
                $query->where(function ($q) use ($meId, $targetId) {
                    $q->where('requester_id', $meId)->where('addressee_id', $targetId);
                })->orWhere(function ($q) use ($meId, $targetId) {
                    $q->where('requester_id', $targetId)->where('addressee_id', $meId);
                });
            })
            ->first();

        if (!$connection) {
            abort(response()->json([
                'message' => 'You can only message users who are your friends.',
            ], 403));
        }

        if ($connection->status === 'blocked' && (int) $connection->addressee_id === $meId) {
            abort(response()->json([
                'message' => 'You are blocked and cannot message this user.',
            ], 403));
        }

        if ($connection->status !== 'accepted' && $connection->status !== 'blocked') {
            abort(response()->json([
                'message' => 'You can only message users who are your friends.',
            ], 403));
        }
    }
}
