<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminPostModerationController extends Controller
{
    public function indexReportedPosts(Request $request)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof \Illuminate\Http\JsonResponse) {
            return $admin;
        }

        $reports = Report::query()
            ->where('reportable_type', Post::class)
            ->where('status', 'pending')
            ->whereHasMorph('reportable', [Post::class])
            ->with([
                'user:id,first_name,last_name,email',
                'reportable.user:id,first_name,last_name,email',
            ])
            ->latest('created_at')
            ->get();

        $data = $reports
            ->groupBy('reportable_id')
            ->map(function ($group) {
                $latest = $group->first();
                $post = $latest?->reportable;

                return [
                    'post_id' => $latest->reportable_id,
                    'report_count' => $group->count(),
                    'latest_report_at' => $latest->created_at,
                    'latest_reason' => $latest->reason,
                    'post' => $post ? [
                        'id' => $post->id,
                        'title' => $post->title,
                        'content' => $post->content,
                        'created_at' => $post->created_at,
                        'user' => $post->user,
                    ] : null,
                    'reporters' => $group
                        ->pluck('user')
                        ->filter()
                        ->unique('id')
                        ->values(),
                ];
            })
            ->filter(fn ($entry) => !is_null($entry['post']))
            ->sortByDesc('latest_report_at')
            ->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function deleteReportedPost(Request $request, int $postId)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof \Illuminate\Http\JsonResponse) {
            return $admin;
        }

        $post = Post::with('media')->find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        DB::transaction(function () use ($post, $admin) {
            foreach ($post->media as $media) {
                if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                    Storage::disk('public')->delete($media->file_path);
                }
            }

            $affectedReports = Report::query()
                ->where('reportable_type', Post::class)
                ->where('reportable_id', $post->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'resolved_deleted',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

            DB::table('admin_action_logs')->insert([
                'admin_id' => $admin->id,
                'action' => 'delete_post',
                'target_type' => Post::class,
                'target_id' => $post->id,
                'metadata' => json_encode([
                    'post_owner_id' => $post->user_id,
                    'resolved_reports' => $affectedReports,
                ]),
                'created_at' => now(),
            ]);

            $post->delete();
        });

        return response()->json([
            'message' => 'Post deleted successfully by admin.',
        ]);
    }

    private function requireAdmin(Request $request)
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
