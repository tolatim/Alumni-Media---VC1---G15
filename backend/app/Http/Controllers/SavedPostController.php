<?php

namespace App\Http\Controllers;

use App\Events\PostSaved;
use App\Events\PostUnsaved;
use App\Models\Post;
use App\Models\SavedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedPostController extends Controller
{
    // GET /api/saved-posts
    // Returns all posts saved by the logged-in user
    public function index(Request $request): JsonResponse
    {
        $savedPosts = SavedPost::with('post')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($savedPosts);
    }

    // POST /api/saved-posts
    // Save a post
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
        ]);

        // Prevent duplicates (table has unique constraint too, but handle gracefully)
        $existing = SavedPost::where('user_id', Auth::id())
            ->where('post_id', $request->post_id)
            ->first();

        if ($existing) {
            return response()->json([
                'message'    => 'Post already saved.',
                'saved_post' => $existing->load('post'),
            ], 200);
        }

        $savedPost = SavedPost::create([
            'user_id' => Auth::id(),
            'post_id' => $request->post_id,
        ]);

        // 🔥 Broadcast WebSocket event
        broadcast(new PostSaved($savedPost));

        return response()->json($savedPost->load('post'), 201);
    }

    // DELETE /api/saved-posts/{post_id}
    // Unsave a post  (we use post_id in the URL, not savedPost id)
    public function destroy(int $postId): JsonResponse
    {
        $savedPost = SavedPost::where('user_id', Auth::id())
            ->where('post_id', $postId)
            ->firstOrFail();

        $savedPost->delete();

        // 🔥 Broadcast WebSocket event
        broadcast(new PostUnsaved($postId, Auth::id()));

        return response()->json(['message' => 'Post unsaved.']);
    }

    // POST /api/saved-posts/toggle
    // Save if not saved, unsave if already saved — handy for a single button
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
        ]);

        $existing = SavedPost::where('user_id', Auth::id())
            ->where('post_id', $request->post_id)
            ->first();

        if ($existing) {
            $existing->delete();
            broadcast(new PostUnsaved($request->post_id, Auth::id()));

            return response()->json([
                'saved'   => false,
                'post_id' => $request->post_id,
            ]);
        }

        $savedPost = SavedPost::create([
            'user_id' => Auth::id(),
            'post_id' => $request->post_id,
        ]);

        broadcast(new PostSaved($savedPost));

        return response()->json([
            'saved'      => true,
            'saved_post' => $savedPost->load('post'),
        ], 201);
    }
}