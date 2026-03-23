<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
<<<<<<<<< Temporary merge branch 1
use App\Models\Connection;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
=========
use App\Models\Report;
>>>>>>>>> Temporary merge branch 2
use Illuminate\Support\Facades\Storage;


class PostController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        $posts = Post::query()
            ->with(['user.role', 'media'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10240',
            'videos.*' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mov|max:20480'
        ]);

        $actor = $request->user();

        $hasText = !empty(trim($validated['title'] ?? '')) || !empty(trim($validated['content'] ?? ''));
        $hasMedia = $request->hasFile('images') || $request->hasFile('videos');

        if (!$hasText && !$hasMedia) {
            return response()->json([
                'message' => 'Please add title, content, or at least one image/video.'
            ], 422);
        }

        $post = Post::create([
            'user_id' => $actor?->id,
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $path = MediaStorageService::storePostMedia($imageFile, (int) $actor->id, 'image');
                $post->media()->create([
                    'file_path' => $path,
                    'type' => 'image'
                ]);
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $videoFile) {
                $path = MediaStorageService::storePostMedia($videoFile, (int) $actor->id, 'video');
                $post->media()->create([
                    'file_path' => $path,
                    'type' => 'video'
                ]);
            }
        }

        if ($actor && Schema::hasTable('connections')) {
            // Fetch all accepted connections for the actor
            $connections = Connection::query()
                ->where('status', 'accepted')
                ->where(function ($query) use ($actor) {
                    $query->where('requester_id', $actor->id)
                        ->orWhere('addressee_id', $actor->id);
                })
                ->get(['requester_id', 'addressee_id']);

            // Map connections to target user IDs (the "other" user)
            $targetIds = $connections->map(
                fn($connection) =>
                (int) ($connection->requester_id === (int) $actor->id
                    ? $connection->addressee_id
                    : $connection->requester_id)
            )->unique()->values()->all();

            // Notify all target users
            if (!empty($targetIds)) {
                $users = User::whereIn('id', $targetIds)->get();
                foreach ($users as $user) {
                    NotificationService::send(
                        $user->id,
                        'New Post',
                        $actor->first_name . ' ' . $actor->last_name . ' published a new post.',
                        'post',
                        $post->id
                    );
                }
            }
        }  

        // Return post with media
        return response()->json([
            'message' => 'Post created successfully!',
            'post'    => $post->load('media', 'user')->loadCount(['likes', 'comments'])
        ], 201);
    }

    public function show($id)
    {
        $post = Post::with(['user.role', 'media'])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $post = Post::with('media')->find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ((int) $post->user_id !== (int) $user->id) {
            return response()->json(['message' => 'You can edit only your own posts.'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'videos.*' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mov,video/quicktime,video/webm|max:20480',
        ]);

        $titleValue = trim((string) ($validated['title'] ?? ''));
        $contentValue = trim((string) ($validated['content'] ?? ''));
        $hasUploadedMedia = $request->hasFile('images') || $request->hasFile('videos');
        $hasExistingMedia = $post->media()->exists();

        if ($titleValue === '' && $contentValue === '' && !$hasUploadedMedia && !$hasExistingMedia) {
            return response()->json([
                'message' => 'Please add title, content, or at least one image/video.',
            ], 422);
        }

        $post->update([
            'title' => $titleValue !== '' ? $titleValue : null,
            'content' => $contentValue !== '' ? $contentValue : null,
        ]);

        $uploadedMedia = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $uploadedMedia[] = [
                    'file' => $imageFile,
                    'type' => 'image',
                ];
            }
        }
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $videoFile) {
                $uploadedMedia[] = [
                    'file' => $videoFile,
                    'type' => 'video',
                ];
            }
        }

        if (!empty($uploadedMedia)) {
            $existingMedia = $post->media()->orderBy('id')->get();
            $firstExisting = $existingMedia->first();
            $firstUploaded = array_shift($uploadedMedia);

            if ($firstUploaded) {
                $newPath = MediaStorageService::storePostMedia($firstUploaded['file'], (int) $user->id, $firstUploaded['type']);

                if ($firstExisting) {
                    MediaStorageService::deletePublicFile($firstExisting->file_path);

                    $firstExisting->update([
                        'file_path' => $newPath,
                        'type' => $firstUploaded['type'],
                    ]);
                } else {
                    $post->media()->create([
                        'file_path' => $newPath,
                        'type' => $firstUploaded['type'],
                    ]);
                }
            }

            foreach ($uploadedMedia as $item) {
                $path = MediaStorageService::storePostMedia($item['file'], (int) $user->id, $item['type']);
                $post->media()->create([
                    'file_path' => $path,
                    'type' => $item['type'],
                ]);
            }
        }

        $updatedPost = $post
            ->fresh()
            ->load('media', 'user.role')
            ->loadCount(['likes', 'comments']);

        try {
            Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('http://localhost:3000/event', [
                'type' => 'post_updated',
                'data' => [
                    'post' => $updatedPost,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('WebSocket event failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $updatedPost,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $post = Post::with('media')->find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ((int) $post->user_id !== (int) $user->id) {
            return response()->json(['message' => 'You can delete only your own posts.'], 403);
        }

        foreach ($post->media as $media) {
            MediaStorageService::deletePublicFile($media->file_path);
        }

        $deletedPostId = $post->id;
        $post->delete();

        try {
            Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('http://localhost:3000/event', [
                'type' => 'post_deleted',
                'data' => [
                    'post_id' => $deletedPostId,
                    'user_id' => $user->id,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('WebSocket event failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Post deleted successfully']);
    }

    public function report(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $post = Post::query()->find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        if ((int) $post->user_id === (int) $user->id) {
            return response()->json(['message' => 'You cannot report your own post.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $existingPending = Report::query()
            ->where('user_id', $user->id)
            ->where('reportable_type', Post::class)
            ->where('reportable_id', $post->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            $existingPending->update([
                'reason' => $validated['reason'],
            ]);

            return response()->json([
                'message' => 'Your report was already submitted. Reason updated.',
                'data' => [
                    'report_id' => $existingPending->id,
                ],
            ]);
        }

        $report = Report::query()->create([
            'user_id' => $user->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Report submitted successfully.',
            'data' => [
                'report_id' => $report->id,
            ],
        ], 201);
    }
}
