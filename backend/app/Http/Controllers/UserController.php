<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Post;
use App\Models\User;
use App\Support\WebsocketNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return response()->json([
            'message' => 'Users fetched successfully',
            'data' => $users,
        ]);
    }


    public function feed(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        if (!Schema::hasTable('posts')) {
            return response()->json([
                'message' => 'Feed fetched successfully',
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
            ]);
        }

        $user = auth()->user();

        $connectionIds = [];

        if ($user && Schema::hasTable('connections')) {

            $connections = Connection::where('status', 'accepted')
                ->where(function ($q) use ($user) {
                    $q->where('requester_id', $user->id)
                        ->orWhere('addressee_id', $user->id);
                })
                ->get();

            foreach ($connections as $connection) {
                $connectionIds[] =
                    $connection->requester_id == $user->id
                    ? $connection->addressee_id
                    : $connection->requester_id;
            }
        }

        $query = Post::query()
            ->whereIn('user_id', $connectionIds)
            ->latest()
            ->with(['user.role']);

        if ($user) {
            $allowedUserIds = [(int) $user->id];

            if (Schema::hasTable('connections')) {
                $friendIds = Connection::query()
                    ->where('status', 'accepted')
                    ->where(function ($connectionQuery) use ($user) {
                        $connectionQuery->where('requester_id', $user->id)
                            ->orWhere('addressee_id', $user->id);
                    })
                    ->get()
                    ->map(function ($row) use ($user) {
                        return (int) ($row->requester_id === (int) $user->id
                            ? $row->addressee_id
                            : $row->requester_id);
                    })
                    ->values()
                    ->all();

                $allowedUserIds = array_values(array_unique(array_merge($allowedUserIds, $friendIds)));
            }

            $query->whereIn('user_id', $allowedUserIds);
        }

        if (Schema::hasTable('media')) {
            $query->with(['media']);
        }

        $countableRelations = [];

        if (Schema::hasTable('likes')) {
            $countableRelations[] = 'likes';
        }

        if (Schema::hasTable('comments')) {
            $countableRelations[] = 'comments';
        }

        if (!empty($countableRelations)) {
            $query->withCount($countableRelations);
        }

        if ($user && Schema::hasTable('likes')) {
            $query->withExists([
                'likes as liked_by_me' => function ($likeQuery) use ($user) {
                    $likeQuery->where('user_id', $user->id);
                },
            ]);
        }

        $posts = $query->paginate($perPage);

        return response()->json([
            'message' => 'Feed fetched successfully',
            'data' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function pendingConnections(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
                'data' => [],
                'pagination' => null,
            ], 503);
        }

        $user = $request->user();

        $pending = Connection::query()
            ->with(['requester.role'])
            ->where('addressee_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Pending connections fetched successfully',
            'data' => $pending->items(),
            'pagination' => [
                'current_page' => $pending->currentPage(),
                'last_page' => $pending->lastPage(),
                'per_page' => $pending->perPage(),
                'total' => $pending->total(),
            ],
        ]);
    }

    public function myConnections(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
                'data' => [],
                'count' => 0,
                'pagination' => null,
            ], 503);
        }

        $user = $request->user();

        $connections = Connection::query()
            ->with(['requester.role', 'addressee.role'])
            ->where('status', 'accepted')
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'My connections fetched successfully',
            'data' => $connections->items(),
            'count' => $connections->total(),
            'pagination' => [
                'current_page' => $connections->currentPage(),
                'last_page' => $connections->lastPage(),
                'per_page' => $connections->perPage(),
                'total' => $connections->total(),
            ],
        ]);
    }

    public function blockedConnections(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
                'data' => [],
                'pagination' => null,
            ], 503);
        }

        $user = $request->user();

        $blocked = Connection::query()
            ->with(['requester.role', 'addressee.role'])
            ->where('status', 'blocked')
            ->where('requester_id', $user->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Blocked connections fetched successfully',
            'data' => $blocked->items(),
            'pagination' => [
                'current_page' => $blocked->currentPage(),
                'last_page' => $blocked->lastPage(),
                'per_page' => $blocked->perPage(),
                'total' => $blocked->total(),
            ],
        ]);
    }

    public function connectionStatus(Request $request, $userId)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
                'data' => ['status' => 'none', 'connection' => null, 'blocked_by_me' => false, 'blocked_me' => false],
            ], 503);
        }

        $me = $request->user();
        $targetId = (int) $userId;

        if ($targetId === (int) $me->id) {
            return response()->json([
                'message' => 'Self profile',
                'data' => ['status' => 'self', 'connection' => null, 'blocked_by_me' => false, 'blocked_me' => false],
            ]);
        }

        $connection = Connection::query()
            ->where(function ($query) use ($me, $targetId) {
                $query->where('requester_id', $me->id)
                    ->where('addressee_id', $targetId);
            })
            ->orWhere(function ($query) use ($me, $targetId) {
                $query->where('requester_id', $targetId)
                    ->where('addressee_id', $me->id);
            })
            ->first();

        $blockedByMe = (bool) ($connection && $connection->status === 'blocked' && (int) $connection->requester_id === (int) $me->id);
        $blockedMe = (bool) ($connection && $connection->status === 'blocked' && (int) $connection->addressee_id === (int) $me->id);

        return response()->json([
            'message' => 'Connection status fetched successfully',
            'data' => [
                'status' => $connection?->status ?? 'none',
                'connection' => $connection,
                'blocked_by_me' => $blockedByMe,
                'blocked_me' => $blockedMe,
            ],
        ]);
    }

    public function sendConnectionRequest(Request $request)
    {

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $me = $request->user();
        $targetId = (int) $validated['user_id'];

        if ($targetId === (int) $me->id) {
            return response()->json([
                'message' => 'You cannot connect with yourself.',
            ], 422);
        }

        $existing = Connection::query()
            ->where(function ($query) use ($me, $targetId) {
                $query->where('requester_id', $me->id)
                    ->where('addressee_id', $targetId);
            })
            ->orWhere(function ($query) use ($me, $targetId) {
                $query->where('requester_id', $targetId)
                    ->where('addressee_id', $me->id);
            })
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Connection already exists or is pending.',
                'data' => $existing,
            ], 409);
        }

        $connection = Connection::create([
            'requester_id' => $me->id,
            'addressee_id' => $targetId,
            'status' => 'pending',
        ]);

        WebsocketNotifier::send('connection_request', [
            'requester_id' => $me->id,
            'addressee_id' => $targetId,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Connection request sent successfully',
            'data' => $connection->load(['requester', 'addressee']),
        ], 201);
    }

    public function acceptConnection(Request $request, $id)
    {
        $me = $request->user();

        $connection = Connection::query()
            ->where('id', $id)
            ->where('addressee_id', $me->id)
            ->where('status', 'pending')
            ->first();

        if (!$connection) {
            return response()->json([
                'message' => 'Connection request not found.',
            ], 404);
        }

        $connection->update(['status' => 'accepted']);

        WebsocketNotifier::send('accept_request', [
            'requester_id' => $connection->requester_id,
            'addressee_id' => $connection->addressee_id,
            'status' => 'accepted',
        ]);

        return response()->json([
            'message' => 'Connection accepted successfully',
            'data' => $connection->fresh()->load(['requester', 'addressee']),
        ]);
    }

    public function rejectConnection(Request $request, $id)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
            ], 503);
        }

        $me = $request->user();

        $connection = Connection::query()
            ->where('id', $id)
            ->where('addressee_id', $me->id)
            ->where('status', 'pending')
            ->first();

        if (!$connection) {
            return response()->json([
                'message' => 'Connection request not found.',
            ], 404);
        }
        
        WebsocketNotifier::send('reject', [
            'requester_id' => $connection->requester_id,
            'addressee_id' => $connection->addressee_id,
        ]);

        $connection->delete();

        return response()->json([
            'message' => 'Connection rejected successfully',
        ]);
    }

    public function unfriend(Request $request, $userId)
    {

        $me = $request->user();
        $targetId = (int) $userId;

        $connection = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($me, $targetId) {
                $query->where(function ($q) use ($me, $targetId) {
                    $q->where('requester_id', $me->id)->where('addressee_id', $targetId);
                })->orWhere(function ($q) use ($me, $targetId) {
                    $q->where('requester_id', $targetId)->where('addressee_id', $me->id);
                });
            })
            ->first();

        if (!$connection) {
            return response()->json([
                'message' => 'Friend connection not found.',
            ], 404);
        }
        WebsocketNotifier::send('unfriend', [
            'requester_id' => $connection->requester_id,
            'addressee_id' => $connection->addressee_id,
        ]);
        $connection->delete();

        return response()->json([
            'message' => 'Unfriended successfully',
        ]);
    }

    public function blockUser(Request $request, $userId)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
            ], 503);
        }

        $me = $request->user();
        $targetId = (int) $userId;

        if ($targetId === (int) $me->id) {
            return response()->json([
                'message' => 'You cannot block yourself.',
            ], 422);
        }

        $connection = Connection::query()
            ->where(function ($query) use ($me, $targetId) {
                $query->where(function ($q) use ($me, $targetId) {
                    $q->where('requester_id', $me->id)
                        ->where('addressee_id', $targetId);
                })->orWhere(function ($q) use ($me, $targetId) {
                    $q->where('requester_id', $targetId)
                        ->where('addressee_id', $me->id);
                });
            })
            ->first();

        if (!$connection) {
            $connection = Connection::create([
                'requester_id' => $me->id,
                'addressee_id' => $targetId,
                'status' => 'blocked',
            ]);
        } else {
            // requester_id = blocker, addressee_id = blocked user
            $connection->update([
                'requester_id' => $me->id,
                'addressee_id' => $targetId,
                'status' => 'blocked',
            ]);
        }

        WebsocketNotifier::send('block', [
            'blocker_id' => $connection->addressee_id,
            'actor_id' => $connection->requester_id,
        ]);

        return response()->json([
            'message' => 'User blocked successfully',
            'data' => $connection->fresh()->load(['requester', 'addressee']),
        ]);
    }

    public function unblockUser(Request $request, $userId)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
            ], 503);
        }

        $me = $request->user();
        $targetId = (int) $userId;

        if ($targetId === (int) $me->id) {
            return response()->json([
                'message' => 'You cannot unblock yourself.',
            ], 422);
        }

        $connection = Connection::query()
            ->where('status', 'blocked')
            ->where('requester_id', $me->id)
            ->where('addressee_id', $targetId)
            ->first();

        if (!$connection) {
            return response()->json([
                'message' => 'Blocked connection not found.',
            ], 404);
        }

        $connection->update([
            'status' => 'accepted',
        ]);

        return response()->json([
            'message' => 'User unblocked successfully',
            'data' => $connection->fresh()->load(['requester', 'addressee']),
        ]);
    }

    public function show(Request $request, $id)
    {
        $authUser = $request->user();

        $query = User::query()->with(['role']);

        if (Schema::hasTable('posts')) {
            $query->with([
                'posts' => function ($postQuery) use ($authUser) {
                    $postQuery->latest();
                    if (Schema::hasTable('media')) {
                        $postQuery->with('media');
                    }

                    $countableRelations = [];
                    if (Schema::hasTable('likes')) {
                        $countableRelations[] = 'likes';
                    }
                    if (Schema::hasTable('comments')) {
                        $countableRelations[] = 'comments';
                    }

                    if (!empty($countableRelations)) {
                        $postQuery->withCount($countableRelations);
                    }

                    if ($authUser && Schema::hasTable('likes')) {
                        $postQuery->withExists([
                            'likes as liked_by_me' => function ($likeQuery) use ($authUser) {
                                $likeQuery->where('user_id', $authUser->id);
                            },
                        ]);
                    }
                },
            ]);
        }

        $profileUser = $query->find($id);

        if (!$profileUser) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'message' => 'User fetched successfully',
            'data' => $profileUser,
        ]);
    }

    public function suggestions(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 8), 1), 50);
        $me = $request->user();

        $excludedIds = [];

        if (Schema::hasTable('connections')) {
            $excludedIds = Connection::query()
                ->where(function ($query) use ($me) {
                    $query->where('requester_id', $me->id)
                        ->orWhere('addressee_id', $me->id);
                })
                ->whereIn('status', ['pending', 'accepted', 'blocked'])
                ->get()
                ->map(function ($connection) use ($me) {
                    return (int) ($connection->requester_id === $me->id
                        ? $connection->addressee_id
                        : $connection->requester_id);
                })
                ->values()
                ->all();
        }

        $suggestions = User::query()
            ->where('id', '!=', $me->id)
            ->when(!empty($excludedIds), function ($query) use ($excludedIds) {
                $query->whereNotIn('id', $excludedIds);
            })
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Suggestions fetched successfully',
            'data' => $suggestions->items(),
            'pagination' => [
                'current_page' => $suggestions->currentPage(),
                'last_page' => $suggestions->lastPage(),
                'per_page' => $suggestions->perPage(),
                'total' => $suggestions->total(),
            ],
        ]);
    }

    public function updateMyProfile(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:255',
            'headline' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:5000',
            'skills' => 'nullable|string|max:2000',
            'avatar_file' => 'nullable|image|max:5120',
            'cover_file' => 'nullable|image|max:8192',
            'location' => 'nullable|string|max:255',
            'graduate_year' => 'nullable|integer|min:1900|max:2100',
            'current_job' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $avatarPath = $user->getRawOriginal('avatar');
        $coverPath = $user->getRawOriginal('cover');

        if ($request->hasFile('avatar_file')) {
            $this->deleteLocalPublicFile($user->getRawOriginal('avatar'));
            $avatarPath = $request->file('avatar_file')->store('profiles/avatars', 'public');
        }

        if ($request->hasFile('cover_file')) {
            $this->deleteLocalPublicFile($user->getRawOriginal('cover'));
            $coverPath = $request->file('cover_file')->store('profiles/covers', 'public');
        }

        $firstName = $validated['first_name'] ?? null;
        $lastName = $validated['last_name'] ?? null;

        if ((!$firstName || !$lastName) && !empty($validated['name'])) {
            $parts = preg_split('/\s+/', trim($validated['name']));
            $firstName = $firstName ?: ($parts[0] ?? '');
            $lastName = $lastName ?: (count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '');
        }

        if (!$firstName || !$lastName) {
            return response()->json([
                'message' => 'First name and last name are required.',
            ], 422);
        }

        $user->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'headline' => $validated['headline'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'avatar' => $avatarPath,
            'cover' => $coverPath,
            'location' => $validated['location'] ?? null,
            'graduate_year' => $validated['graduate_year'] ?? ($validated['education'] ?? null),
            'current_job' => $validated['current_job'] ?? ($validated['position'] ?? null),
            'company' => $validated['company'] ?? null,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()->load(['role']),
        ]);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed|different:old_password',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['old_password'], $user->password)) {
            return response()->json([
                'message' => 'Old password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => $validated['new_password'],
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    private function deleteLocalPublicFile(?string $value): void
    {
        if (!$value) {
            return;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return;
        }

        if (Str::startsWith($value, '/storage/')) {
            $value = Str::replaceFirst('/storage/', '', $value);
        }

        if (Storage::disk('public')->exists($value)) {
            Storage::disk('public')->delete($value);
        }
    }
}
