<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, $postId)
    {
        $user = $request->user();
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $existingLike = Like::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();

            return response()->json([
                'message' => 'Post unliked successfully',
                'liked' => false,
                'likes_count' => $post->likes()->count(),
            ]);
        }

        Like::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Trigger notification
        NotificationService::notifyPostLiked($post, $user);

        return response()->json([
            'message' => 'Post liked successfully',
            'liked' => true,
            'likes_count' => $post->likes()->count(),
        ], 201);
    }
}
