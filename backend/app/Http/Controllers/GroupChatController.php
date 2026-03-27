<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\GroupChat;
use App\Models\GroupChatMessage;
use App\Models\User;
use App\Support\WebsocketNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GroupChatController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->groupTablesExist()) {
            return $this->groupTablesMissingResponse();
        }

        $me = $request->user();

        $groups = GroupChat::query()
            ->whereHas('members', function ($query) use ($me) {
                $query->where('users.id', $me->id);
            })
            ->with([
                'owner',
                'members',
                'messages' => function ($query) {
                    $query->latest()->limit(1)->with('sender');
                },
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Group chats fetched successfully',
            'data' => $groups,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->groupTablesExist()) {
            return $this->groupTablesMissingResponse();
        }

        $me = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|distinct|exists:users,id',
        ]);

        $memberIds = collect($validated['member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $me->id)
            ->unique()
            ->values();

        foreach ($memberIds as $memberId) {
            $this->assertCanInvite($me->id, $memberId);
        }

        $group = GroupChat::create([
            'name' => trim((string) $validated['name']),
            'owner_id' => $me->id,
        ]);

        $group->members()->attach(array_merge([$me->id], $memberIds->all()));

        return response()->json([
            'message' => 'Group created successfully',
            'data' => $group->load(['owner', 'members']),
        ], 201);
    }

    public function invite(Request $request, int $groupId)
    {
        if (!$this->groupTablesExist()) {
            return $this->groupTablesMissingResponse();
        }

        $me = $request->user();

        $group = GroupChat::query()->with('members')->find($groupId);
        if (!$group) {
            return response()->json([
                'message' => 'Group not found.',
            ], 404);
        }

        $this->assertGroupMember($group, $me->id);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $targetId = (int) $validated['user_id'];
        if ($targetId === (int) $me->id) {
            return response()->json([
                'message' => 'You are already in this group.',
            ], 422);
        }

        $alreadyMember = $group->members->contains(fn ($member) => (int) $member->id === $targetId);
        if ($alreadyMember) {
            return response()->json([
                'message' => 'User is already a group member.',
            ], 422);
        }

        $this->assertCanInvite($me->id, $targetId);

        $group->members()->attach($targetId);

        return response()->json([
            'message' => 'Friend invited successfully',
            'data' => $group->fresh()->load(['owner', 'members']),
        ]);
    }

    public function messages(Request $request, int $groupId)
    {
        if (!$this->groupTablesExist()) {
            return $this->groupTablesMissingResponse();
        }

        $me = $request->user();
        $group = GroupChat::query()->find($groupId);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found.',
            ], 404);
        }

        $this->assertGroupMember($group, $me->id);

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $messages = GroupChatMessage::query()
            ->where('group_chat_id', $group->id)
            ->with('sender')
            ->latest()
            ->paginate($perPage);

        $items = collect($messages->items())
            ->reverse()
            ->values();

        return response()->json([
            'message' => 'Group messages fetched successfully',
            'data' => $items,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function sendMessage(Request $request, int $groupId)
    {
        if (!$this->groupTablesExist()) {
            return $this->groupTablesMissingResponse();
        }

        $me = $request->user();
        $group = GroupChat::query()->find($groupId);

        if (!$group) {
            return response()->json([
                'message' => 'Group not found.',
            ], 404);
        }

        $this->assertGroupMember($group, $me->id);

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

        $message = GroupChatMessage::create([
            'group_chat_id' => $group->id,
            'sender_id' => $me->id,
            'content' => $validated['content'] ?? null,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
        ])->load('sender');

        $targetUserIds = $group->members()
            ->where('users.id', '!=', $me->id)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        WebsocketNotifier::send('group_message', [
            'target_user_ids' => $targetUserIds,
            'group_id' => (int) $group->id,
            'sender_id' => (int) $me->id,
            'message' => $message->toArray(),
        ]);

        return response()->json([
            'message' => 'Group message sent successfully',
            'data' => $message,
        ], 201);
    }

    private function assertGroupMember(GroupChat $group, int $userId): void
    {
        $isMember = $group->members()
            ->where('users.id', $userId)
            ->exists();

        if (!$isMember) {
            abort(response()->json([
                'message' => 'You are not a member of this group.',
            ], 403));
        }
    }

    private function assertCanInvite(int $inviterId, int $targetId): void
    {
        if (!User::query()->where('id', $targetId)->exists()) {
            abort(response()->json([
                'message' => 'User not found.',
            ], 404));
        }

        $connection = Connection::query()
            ->where(function ($query) use ($inviterId, $targetId) {
                $query->where(function ($q) use ($inviterId, $targetId) {
                    $q->where('requester_id', $inviterId)->where('addressee_id', $targetId);
                })->orWhere(function ($q) use ($inviterId, $targetId) {
                    $q->where('requester_id', $targetId)->where('addressee_id', $inviterId);
                });
            })
            ->first();

        if (!$connection || $connection->status !== 'accepted') {
            abort(response()->json([
                'message' => 'You can only invite accepted friends.',
            ], 403));
        }
    }

    private function groupTablesExist(): bool
    {
        return Schema::hasTable('group_chats')
            && Schema::hasTable('group_chat_members')
            && Schema::hasTable('group_chat_messages');
    }

    private function groupTablesMissingResponse()
    {
        return response()->json([
            'message' => 'Group chat tables are missing. Please run database migrations.',
        ], 503);
    }
}
