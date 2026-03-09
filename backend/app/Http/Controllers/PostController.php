<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        return Post::with(['user', 'media'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();
    }

    // store post
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10240',
            'videos.*' => 'nullable|file|mimetypes:video/mp4,video/avi,video/mov|max:20480'
        ]);

        // Ensure there is at least text or media
        $hasText = !empty(trim($validated['title'] ?? '')) || !empty(trim($validated['content'] ?? ''));
        $hasMedia = $request->hasFile('images') || $request->hasFile('videos');

        if (!$hasText && !$hasMedia) {
            return response()->json([
                'message' => 'Please add title, content, or at least one image/video.'
            ], 422);
        }

        // Create the post first
        $post = Post::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'] ?? null,
        ]);

        // Upload images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('posts/images', 'public');
                $post->media()->create([
                    'file_path' => $path,
                    'type' => 'image'
                ]);
            }
        }

        // Upload videos
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $videoFile) {
                $path = $videoFile->store('posts/videos', 'public');
                $post->media()->create([
                    'file_path' => $path,
                    'type' => 'video'
                ]);
            }
        }

        // Return post with media
        return response()->json([
            'message' => 'Post created successfully!',
            'post' => $post->load('media', 'user')->loadCount(['likes', 'comments'])
        ], 201);
    }

    // show post
    public function show($id)
    {
        $post = Post::with(['user', 'media'])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        return response()->json($post);
    }

    // update post
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

        if (!empty($uploadedMedia)) {
            $existingMedia = $post->media()->orderBy('id')->get();
            $firstExisting = $existingMedia->first();
            $firstUploaded = array_shift($uploadedMedia);

            if ($firstUploaded) {
                $newPath = $firstUploaded['file']->store($firstUploaded['dir'], 'public');

                // Replace only the first old media item, keep others untouched.
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

            // Any extra selected files are added as new media.
            foreach ($uploadedMedia as $item) {
                $path = $item['file']->store($item['dir'], 'public');
                $post->media()->create([
                    'file_path' => $path,
                    'type' => $item['type'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->fresh()->load('media', 'user')->loadCount(['likes', 'comments']),
        ]);
    }

    // delete post
    public function destroy($id)
    {
        $post = Post::with('media')->find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        foreach ($post->media as $media) {
            if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
