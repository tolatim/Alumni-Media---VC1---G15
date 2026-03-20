<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
use App\Models\Connection;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            ->with([
                'requester:id,first_name,last_name,headline,avatar',
                'addressee:id,first_name,last_name,headline,avatar',
            ])
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
                $this->applyConversationFilter($query, $me->id, $targetId);
            })
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
            $file = $request->file('media_file');
            $mime = $file->getMimeType() ?: '';
            $mediaType = str_starts_with($mime, 'video/') ? 'video' : 'image';
            $mediaPath = MediaStorageService::storeMessageMedia($file, $me->id, $targetId, $mediaType);
        }

        $message = Message::create([
            'sender_id' => $me->id,
            'receiver_id' => $targetId,
            'content' => $validated['content'] ?? null,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'status' => 'sent',
        ]);

        Notification::create([
            'user_id' => $targetId,
            'title' => 'New Message',
            'message' => ($me->name ?: 'Someone') . ' sent you a message.',
            'type' => 'message',
            'related_id' => $message->id,
        ]);

        $this->safeBroadcast(new MessageCreated($message));

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

    public function sync(Request $request, $userId)
    {
        $me = $request->user();
        $targetId = (int) $userId;
        $afterId = max((int) $request->query('after_id', 0), 0);

        $this->assertCanMessage($me->id, $targetId);

        $messages = Message::query()
            ->where('id', '>', $afterId)
            ->where(function ($query) use ($me, $targetId) {
                $this->applyConversationFilter($query, $me->id, $targetId);
            })
            ->orderBy('id')
            ->limit(50)
            ->get();

        return response()->json([
            'message' => 'Message sync fetched successfully',
            'data' => $messages,
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
            MediaStorageService::deletePublicFile($message->media_path);
        }

        $deletedId = $message->id;
        $senderId = (int) $message->sender_id;
        $receiverId = (int) $message->receiver_id;

        $message->delete();

        $this->safeBroadcast(new MessageDeleted($deletedId, $senderId, $receiverId));

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

        $message = $message->fresh();

        $this->safeBroadcast(new MessageUpdated($message));

        return response()->json([
            'message' => 'Message updated successfully',
            'data' => $message,
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
                $this->applyConversationFilter($query, $meId, $targetId, 'requester_id', 'addressee_id');
            })
            ->select(['id', 'requester_id', 'addressee_id', 'status'])
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

    private function safeBroadcast(object $event): void
    {
        try {
            broadcast($event)->toOthers();
        } catch (Throwable $exception) {
            Log::warning('Message broadcast failed.', [
                'event' => class_basename($event),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function applyConversationFilter(
        Builder $query,
        int $firstUserId,
        int $secondUserId,
        string $leftColumn = 'sender_id',
        string $rightColumn = 'receiver_id'
    ): void {
        $query
            ->where(function ($nested) use ($firstUserId, $secondUserId, $leftColumn, $rightColumn) {
                $nested->where($leftColumn, $firstUserId)
                    ->where($rightColumn, $secondUserId);
            })
            ->orWhere(function ($nested) use ($firstUserId, $secondUserId, $leftColumn, $rightColumn) {
                $nested->where($leftColumn, $secondUserId)
                    ->where($rightColumn, $firstUserId);
            });
    }

}
