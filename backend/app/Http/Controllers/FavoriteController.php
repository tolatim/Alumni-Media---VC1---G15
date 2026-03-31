<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('favorites')) {
            return response()->json([
                'message' => 'Favorite posts fetched successfully.',
                'data' => [],
            ]);
        }

        $user = $request->user();

        $posts = Post::query()
            ->withCardData($user)
            ->select('posts.*')
            ->join('favorites', 'favorites.post_id', '=', 'posts.id')
            ->where('favorites.user_id', $user->id)
            ->orderByDesc('favorites.created_at')
            ->get();

        return response()->json([
            'message' => 'Favorite posts fetched successfully.',
            'data' => $posts,
        ]);
    }

    public function toggle(Request $request, Post $post)
    {
        if (!Schema::hasTable('favorites')) {
            return response()->json([
                'message' => 'Favorites are not available. Run migrations first.',
            ], 503);
        }

        $user = $request->user();

        $existingFavorite = Favorite::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingFavorite) {
            $existingFavorite->delete();

            return response()->json([
                'message' => 'Post removed from favorites.',
                'favorited' => false,
                'favorites_count' => $post->favorites()->count(),
            ]);
        }

        Favorite::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Post added to favorites.',
            'favorited' => true,
            'favorites_count' => $post->favorites()->count(),
        ], 201);
    }
}
