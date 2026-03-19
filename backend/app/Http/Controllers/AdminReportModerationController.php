<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Support\WebsocketNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdminReportModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $reports = Report::query()
            ->with(['user:id,first_name,last_name,email', 'reportable'])
            ->where('status', 'pending')
            ->latest('created_at')
            ->get()
            ->map(function (Report $report) {
                $target = $report->reportable;
                $targetType = class_basename($report->reportable_type);

                $targetSummary = null;
                $targetUserId = null;

                if ($target instanceof Post) {
                    $targetSummary = $target->title ?: substr((string) $target->content, 0, 80);
                    $targetUserId = $target->user_id;
                } elseif ($target instanceof User) {
                    $targetSummary = $target->name ?: $target->email;
                    $targetUserId = $target->id;
                }

                return [
                    'id' => $report->id,
                    'reason' => $report->reason,
                    'status' => $report->status,
                    'created_at' => $report->created_at,
                    'reporter' => $report->user ? [
                        'id' => $report->user->id,
                        'name' => $report->user->name,
                        'email' => $report->user->email,
                    ] : null,
                    'target' => [
                        'type' => strtolower($targetType),
                        'id' => $report->reportable_id,
                        'summary' => $targetSummary,
                        'user_id' => $targetUserId,
                        'exists' => !is_null($target),
                    ],
                ];
            });

        return response()->json([
            'data' => $reports,
        ]);
    }

    public function ignore(Request $request, int $reportId): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $report = Report::find($reportId);
        if (!$report) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        $report->update([
            'status' => 'ignored',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        if (Schema::hasTable('admin_action_logs')) {
            DB::table('admin_action_logs')->insert([
                'admin_id' => $admin->id,
                'action' => 'ignore_report',
                'target_type' => Report::class,
                'target_id' => $report->id,
                'metadata' => json_encode([
                    'reportable_type' => $report->reportable_type,
                    'reportable_id' => $report->reportable_id,
                ]),
                'created_at' => now(),
            ]);
        }

        WebsocketNotifier::send('admin_activity', [
            'event' => 'report_ignored',
            'report_id' => $report->id,
            'admin_id' => $admin->id,
            'occurred_at' => now()->toIso8601String(),
        ], 'admins');

        return response()->json([
            'message' => 'Report ignored successfully.',
        ]);
    }

    public function deletePost(Request $request, int $reportId): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $report = Report::with('reportable')->find($reportId);
        if (!$report) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        if ($report->reportable_type !== Post::class) {
            return response()->json(['message' => 'This report does not target a post.'], 422);
        }

        $post = Post::with('media')->find($report->reportable_id);
        if (!$post) {
            $report->update([
                'status' => 'resolved_deleted',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return response()->json(['message' => 'Post already removed. Report resolved.']);
        }

        DB::transaction(function () use ($admin, $post, $report) {
            foreach ($post->media as $media) {
                if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                    Storage::disk('public')->delete($media->file_path);
                }
            }

            Report::query()
                ->where('reportable_type', Post::class)
                ->where('reportable_id', $post->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved_deleted',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

            if (Schema::hasTable('admin_action_logs')) {
                DB::table('admin_action_logs')->insert([
                    'admin_id' => $admin->id,
                    'action' => 'delete_post_from_report',
                    'target_type' => Post::class,
                    'target_id' => $post->id,
                    'metadata' => json_encode([
                        'report_id' => $report->id,
                    ]),
                    'created_at' => now(),
                ]);
            }

            $post->delete();
        });

        WebsocketNotifier::send('admin_activity', [
            'event' => 'report_post_deleted',
            'report_id' => $report->id,
            'post_id' => $post->id,
            'admin_id' => $admin->id,
            'occurred_at' => now()->toIso8601String(),
        ], 'admins');

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }

    public function suspendUser(Request $request, int $reportId): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) {
            return $admin;
        }

        $validated = $request->validate([
            'duration' => 'required|in:7_days,permanent',
        ]);

        $report = Report::with('reportable')->find($reportId);
        if (!$report) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        $targetUser = $this->extractTargetUser($report);
        if (!$targetUser) {
            return response()->json(['message' => 'Target user not found for this report.'], 422);
        }

        $targetUser->loadMissing('role');

        if ((int) $targetUser->id === (int) $admin->id) {
            return response()->json(['message' => 'You cannot suspend your own account.'], 422);
        }

        if (($targetUser->role->name ?? null) === 'admin') {
            return response()->json(['message' => 'Admin accounts cannot be suspended.'], 422);
        }

        $duration = $validated['duration'];
        $isPermanent = $duration === 'permanent';
        $suspendedUntil = $isPermanent ? null : now()->addDays(7);

        DB::transaction(function () use ($admin, $report, $targetUser, $isPermanent, $suspendedUntil, $duration) {
            $targetUser->forceFill([
                'suspended_permanently' => $isPermanent,
                'suspended_until' => $suspendedUntil,
            ])->save();

            $targetUser->tokens()->delete();

            $report->update([
                'status' => 'resolved_suspended',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if (Schema::hasTable('admin_action_logs')) {
                DB::table('admin_action_logs')->insert([
                    'admin_id' => $admin->id,
                    'action' => 'suspend_user_from_report',
                    'target_type' => User::class,
                    'target_id' => $targetUser->id,
                    'metadata' => json_encode([
                        'report_id' => $report->id,
                        'duration' => $duration,
                        'suspended_until' => $suspendedUntil,
                    ]),
                    'created_at' => now(),
                ]);
            }
        });

        WebsocketNotifier::send('admin_activity', [
            'event' => 'report_user_suspended',
            'report_id' => $report->id,
            'user_id' => $targetUser->id,
            'admin_id' => $admin->id,
            'duration' => $duration,
            'suspended_until' => $suspendedUntil?->toIso8601String(),
            'occurred_at' => now()->toIso8601String(),
        ], 'admins');

        return response()->json([
            'message' => 'User suspended successfully.',
        ]);
    }

    private function extractTargetUser(Report $report): ?User
    {
        if ($report->reportable_type === User::class) {
            return User::find($report->reportable_id);
        }

        if ($report->reportable_type === Post::class) {
            $post = Post::find($report->reportable_id);
            if (!$post) {
                return null;
            }

            return User::find($post->user_id);
        }

        return null;
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
