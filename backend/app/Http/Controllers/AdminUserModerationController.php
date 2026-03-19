<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\WebsocketNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminUserModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $search = trim((string) $request->query('q', ''));
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $query = User::query()
            ->with('role')
            ->whereHas('role', function ($roleQuery) {
                $roleQuery->where('name', '!=', 'admin');
            });

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($perPage);

        $users = collect($paginator->items())
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name,
                    'suspended_until' => $user->suspended_until,
                    'suspended_permanently' => (bool) $user->suspended_permanently,
                    'is_suspended' => $user->isSuspended(),
                ];
            });

        return response()->json([
            'data' => $users,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function suspend(Request $request, int $userId): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $validated = $request->validate([
            'duration' => 'required|in:7_days,permanent',
        ]);

        $target = User::query()->with('role')->find($userId);
        if (!$target) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ((int) $target->id === (int) $admin->id) {
            return response()->json(['message' => 'You cannot suspend your own account.'], 422);
        }

        if (($target->role?->name ?? null) === 'admin') {
            return response()->json(['message' => 'Admin accounts cannot be suspended.'], 422);
        }

        $duration = $validated['duration'];
        $isPermanent = $duration === 'permanent';
        $suspendedUntil = $isPermanent ? null : now()->addDays(7);

        DB::transaction(function () use ($admin, $target, $isPermanent, $suspendedUntil, $duration) {
            $target->forceFill([
                'suspended_permanently' => $isPermanent,
                'suspended_until' => $suspendedUntil,
            ])->save();

            // Revoke active tokens so suspension takes effect immediately.
            $target->tokens()->delete();

            if (Schema::hasTable('admin_action_logs')) {
                DB::table('admin_action_logs')->insert([
                    'admin_id' => $admin->id,
                    'action' => 'suspend_user',
                    'target_type' => User::class,
                    'target_id' => $target->id,
                    'metadata' => json_encode([
                        'duration' => $duration,
                        'suspended_until' => $suspendedUntil,
                    ]),
                    'created_at' => now(),
                ]);
            }
        });

        WebsocketNotifier::send('admin_activity', [
            'event' => 'user_suspended',
            'user_id' => $target->id,
            'admin_id' => $admin->id,
            'duration' => $duration,
            'suspended_until' => $suspendedUntil?->toIso8601String(),
            'occurred_at' => now()->toIso8601String(),
        ], 'admins');

        return response()->json([
            'message' => 'User suspended successfully.',
            'data' => [
                'id' => $target->id,
                'suspended_permanently' => $isPermanent,
                'suspended_until' => $suspendedUntil,
                'is_suspended' => true,
            ],
        ]);
    }

    private function requireAdmin(Request $request): User|JsonResponse
    {
        $user = $request->user()?->loadMissing('role');
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (($user->role->name ?? null) !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        return $user;
    }
}
