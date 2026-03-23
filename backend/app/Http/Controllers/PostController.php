<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Support\WebsocketNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        return Post::query()
            ->withCardData($request->user())
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10240',
            'videos.*' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mov|max:20480',
        ]);

        $hasText = !empty(trim($validated['title'] ?? '')) || !empty(trim($validated['content'] ?? ''));
        $hasMedia = $request->hasFile('images') || $request->hasFile('videos');

        if (!$hasText && !$hasMedia) {
            return response()->json([
                'message' => 'Please add title, content, or at least one image/video.',
            ], 422);
        }

        $post = Post::create([
            'user_id' => $request->user()->id,
            'shared_post_id' => null,
            'title' => $this->emptyToNull($validated['title'] ?? null),
            'content' => $this->emptyToNull($validated['content'] ?? null),
        ]);

        $this->storeUploadedMedia($request, $post);

        $postPayload = $this->loadCardPost($post->id, $request->user());
        WebsocketNotifier::send('post_created', [
            'post' => $postPayload,
        ]);

        return response()->json([
            'message' => 'Post created successfully!',
            'post' => $postPayload,
        ], 201);
    }

    public function share(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'nullable|string|max:5000',
        ]);

        $sourcePost = $this->loadOriginalPost($post, $request->user());
        if (!$sourcePost) {
            return response()->json([
                'message' => 'Original post is no longer available for sharing.',
            ], 422);
        }

        $sharePost = $this->createSharePost(
            $request->user()->id,
            $sourcePost->id,
            $validated['content'] ?? null
        );

        $sharePayload = $this->loadCardPost($sharePost->id, $request->user());
        $updatedSourcePost = $this->loadCardPost($sourcePost->id, $request->user());

        WebsocketNotifier::send('post_created', [
            'post' => $sharePayload,
        ]);

        if ($updatedSourcePost) {
            WebsocketNotifier::send('post_updated', [
                'post' => $updatedSourcePost,
            ]);
        }

        return response()->json([
            'message' => 'Post shared successfully.',
            'post' => $sharePayload,
            'shared_post' => $updatedSourcePost,
        ], 201);
    }

    public function shareList(Request $request, $id)
    {
        $post = Post::query()->find($id);
        $originalPostId = $post ? $post->originalPostId() : (int) $id;

        $shares = Post::query()
            ->where('shared_post_id', $originalPostId)
            ->with(['user.role'])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Post sharers fetched successfully.',
            'data' => $shares,
            'shares_count' => $shares->count(),
            'source_post_id' => $originalPostId,
            'source_post_exists' => Post::query()->whereKey($originalPostId)->exists(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $post = Post::query()
            ->withCardData($request->user())
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

        $isSharePost = $post->isShare();
        if ($isSharePost && ($request->hasFile('images') || $request->hasFile('videos'))) {
            return response()->json([
                'message' => 'Shared posts can only update the share note.',
            ], 422);
        }

        $titleValue = trim((string) ($validated['title'] ?? ''));
        $contentValue = trim((string) ($validated['content'] ?? ''));
        $hasUploadedMedia = $request->hasFile('images') || $request->hasFile('videos');
        $hasExistingMedia = $post->media()->exists();

        if ($titleValue === '' && $contentValue === '' && !$hasUploadedMedia && !$hasExistingMedia && !$isSharePost) {
            return response()->json([
                'message' => 'Please add title, content, or at least one image/video.',
            ], 422);
        }

        $post->update([
            'title' => $isSharePost ? null : $this->emptyToNull($titleValue),
            'content' => $this->emptyToNull($contentValue),
        ]);

        if (!$isSharePost) {
            $this->replaceOrAppendUploadedMedia($request, $post);
        }

        $updatedPost = $this->loadCardPost($post->id, $user);

        WebsocketNotifier::send('post_updated', [
            'post' => $updatedPost,
        ]);

        return response()->json([
            'message' => $isSharePost ? 'Share updated successfully' : 'Post updated successfully',
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

        $user->loadMissing('role');
        $isOwner = (int) $post->user_id === (int) $user->id;
        $isAdmin = ($user->role->name ?? null) === 'admin';

        if (!$isOwner && !$isAdmin) {
            return response()->json(['message' => 'You can delete only your own posts.'], 403);
        }

        $shareSourceId = $post->isShare() ? $post->originalPostId() : null;

        foreach ($post->media as $media) {
            if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }
        }

        $deletedPostId = $post->id;
        $post->delete();

        WebsocketNotifier::send('post_deleted', [
            'post_id' => $deletedPostId,
            'user_id' => $user->id,
        ]);

        if ($shareSourceId) {
            $updatedSourcePost = $this->loadCardPost($shareSourceId, $user);
            if ($updatedSourcePost) {
                WebsocketNotifier::send('post_updated', [
                    'post' => $updatedSourcePost,
                ]);
            }
        }

        return response()->json([
            'message' => $shareSourceId ? 'Share deleted successfully' : 'Post deleted successfully',
        ]);
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

    private function storeUploadedMedia(Request $request, Post $post): void
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('posts/images', 'public');
                $post->media()->create([
                    'file_path' => $path,
                    'type' => 'image',
                ]);
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $videoFile) {
                $path = $videoFile->store('posts/videos', 'public');
                $post->media()->create([
                    'file_path' => $path,
                    'type' => 'video',
                ]);
            }
        }
    }

    private function replaceOrAppendUploadedMedia(Request $request, Post $post): void
    {
        $uploadedMedia = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $uploadedMedia[] = [
                    'file' => $imageFile,
                    'type' => 'image',
                    'dir' => 'posts/images',
                ];
            }
        }

        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $videoFile) {
                $uploadedMedia[] = [
                    'file' => $videoFile,
                    'type' => 'video',
                    'dir' => 'posts/videos',
                ];
            }
        }

        if (empty($uploadedMedia)) {
            return;
        }

        $existingMedia = $post->media()->orderBy('id')->get();
        $firstExisting = $existingMedia->first();
        $firstUploaded = array_shift($uploadedMedia);

        if ($firstUploaded) {
            $newPath = $firstUploaded['file']->store($firstUploaded['dir'], 'public');

            if ($firstExisting) {
                if ($firstExisting->file_path && Storage::disk('public')->exists($firstExisting->file_path)) {
                    Storage::disk('public')->delete($firstExisting->file_path);
                }

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
            $path = $item['file']->store($item['dir'], 'public');
            $post->media()->create([
                'file_path' => $path,
                'type' => $item['type'],
            ]);
        }
    }

    private function loadCardPost(int $postId, $viewer): ?Post
    {
        return Post::query()
            ->withCardData($viewer)
            ->find($postId);
    }

    private function loadOriginalPost(Post $post, $viewer): ?Post
    {
        return $this->loadCardPost($post->originalPostId(), $viewer);
    }

    private function createSharePost(int $userId, int $originalPostId, $content): Post
    {
        return Post::create([
            'user_id' => $userId,
            'shared_post_id' => $originalPostId,
            'title' => null,
            'content' => $this->emptyToNull($content),
        ]);
    }

    private function emptyToNull($value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}
