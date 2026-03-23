<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Support\WebsocketNotifier;
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

            $likesCount = $post->likes()->count();
            WebsocketNotifier::send('post_like_updated', [
                'post_id' => $post->id,
                'likes_count' => $likesCount,
                'actor_user_id' => $user->id,
                'liked' => false,
            ]);

            return response()->json([
                'message' => 'Post unliked successfully',
                'liked' => false,
                'likes_count' => $likesCount,
            ]);
        }

        Like::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $likesCount = $post->likes()->count();
        WebsocketNotifier::send('post_like_updated', [
            'post_id' => $post->id,
            'likes_count' => $likesCount,
            'actor_user_id' => $user->id,
            'liked' => true,
        ]);

        return response()->json([
            'message' => 'Post liked successfully',
            'liked' => true,
            'likes_count' => $likesCount,
        ], 201);
    }
}
