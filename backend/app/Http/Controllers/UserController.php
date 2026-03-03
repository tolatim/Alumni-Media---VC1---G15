<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Post;
use App\Models\User;
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

    public function feed()
    {
        if (!Schema::hasTable('posts')) {
            return response()->json([
                'message' => 'Feed fetched successfully',
                'data' => [],
            ]);
        }

        $query = Post::query()->latest()->with(['user.role']);

        if (Schema::hasTable('post_media')) {
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

        $posts = $query->get();

        return response()->json([
            'message' => 'Feed fetched successfully',
            'data' => $posts,
        ]);
    }

    public function pendingConnections(Request $request)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
                'data' => [],
            ], 503);
        }

        $user = $request->user();

        $pending = Connection::query()
            ->with(['requester.role'])
            ->where('addressee_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Pending connections fetched successfully',
            'data' => $pending,
        ]);
    }

    public function myConnections(Request $request)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
                'data' => [],
                'count' => 0,
            ], 503);
        }

        $user = $request->user();

        $connections = Connection::query()
            ->with(['requester.role', 'addressee.role'])
            ->where('status', 'accepted')
            ->where(function ($query) use ($user) {
                $query
                    ->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => 'My connections fetched successfully',
            'data' => $connections,
            'count' => $connections->count(),
        ]);
    }

    public function connectionStatus(Request $request, $userId)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
                'data' => ['status' => 'none', 'connection' => null],
            ], 503);
        }

        $me = $request->user();
        $targetId = (int) $userId;

        if ($targetId === (int) $me->id) {
            return response()->json([
                'message' => 'Self profile',
                'data' => ['status' => 'self', 'connection' => null],
            ]);
        }

        $connection = Connection::query()
            ->where(function ($query) use ($me, $targetId) {
                $query->where('requester_id', $me->id)->where('addressee_id', $targetId);
            })
            ->orWhere(function ($query) use ($me, $targetId) {
                $query->where('requester_id', $targetId)->where('addressee_id', $me->id);
            })
            ->first();

        return response()->json([
            'message' => 'Connection status fetched successfully',
            'data' => [
                'status' => $connection?->status ?? 'none',
                'connection' => $connection,
            ],
        ]);
    }

    public function sendConnectionRequest(Request $request)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
            ], 503);
        }

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
                $query
                    ->where('requester_id', $me->id)
                    ->where('addressee_id', $targetId);
            })
            ->orWhere(function ($query) use ($me, $targetId) {
                $query
                    ->where('requester_id', $targetId)
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

        return response()->json([
            'message' => 'Connection request sent successfully',
            'data' => $connection->load(['requester', 'addressee']),
        ], 201);
    }

    public function acceptConnection(Request $request, $id)
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

        $connection->update([
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

        $connection->update([
            'status' => 'blocked',
        ]);

        return response()->json([
            'message' => 'Connection blocked successfully',
            'data' => $connection->fresh()->load(['requester', 'addressee']),
        ]);
    }

    public function unfriend(Request $request, $userId)
    {
        if (!Schema::hasTable('connections')) {
            return response()->json([
                'message' => 'Connections table is missing. Run migrations first.',
            ], 503);
        }

        $me = $request->user();
        $targetId = (int) $userId;

        $connection = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($me, $targetId) {
                $query
                    ->where(function ($q) use ($me, $targetId) {
                        $q->where('requester_id', $me->id)->where('addressee_id', $targetId);
                    })
                    ->orWhere(function ($q) use ($me, $targetId) {
                        $q->where('requester_id', $targetId)->where('addressee_id', $me->id);
                    });
            })
            ->first();

        if (!$connection) {
            return response()->json([
                'message' => 'Friend connection not found.',
            ], 404);
        }

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
                $query
                    ->where('requester_id', $me->id)
                    ->where('addressee_id', $targetId);
            })
            ->orWhere(function ($query) use ($me, $targetId) {
                $query
                    ->where('requester_id', $targetId)
                    ->where('addressee_id', $me->id);
            })
            ->first();

        if ($connection) {
            $connection->update(['status' => 'blocked']);
        } else {
            $connection = Connection::create([
                'requester_id' => $me->id,
                'addressee_id' => $targetId,
                'status' => 'blocked',
            ]);
        }

        return response()->json([
            'message' => 'User blocked successfully',
            'data' => $connection,
        ]);
    }

    public function show($id)
    {
        $query = User::query()->with(['role']);

        if (Schema::hasTable('posts')) {
            $query->with([
                'posts' => function ($postQuery) {
                    $postQuery->latest();

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
                },
            ]);
        }

        $user = $query->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'message' => 'User fetched successfully',
            'data' => $user,
        ]);
    }

    public function suggestions(Request $request)
    {
        $me = $request->user();

        $excludedIds = [];

        if (Schema::hasTable('connections')) {
            $excludedIds = Connection::query()
                ->where(function ($query) use ($me) {
                    $query
                        ->where('requester_id', $me->id)
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
            ->inRandomOrder()
            ->limit(8)
            ->get();

        return response()->json([
            'message' => 'Suggestions fetched successfully',
            'data' => $suggestions,
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
            'avatar_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'cover_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
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
