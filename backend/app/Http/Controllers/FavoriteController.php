<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FavoriteController extends Controller
{
    public function toggle(Request $request, $postId)
    {
        if (!Schema::hasTable('favorites')) {
            return response()->json(['message' => 'Favorites table is missing. Run migrations first.'], 503);
        }

        $user = $request->user();
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $existingFavorite = Favorite::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingFavorite) {
            $existingFavorite->delete();

            return response()->json([
                'message' => 'Post removed from favorites',
                'favorited' => false,
                'favorites_count' => $post->favorites()->count(),
            ]);
        }

        Favorite::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Post added to favorites',
            'favorited' => true,
            'favorites_count' => $post->favorites()->count(),
        ], 201);
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('favorites')) {
            return response()->json([
                'message' => 'Favorites fetched successfully',
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                ],
            ]);
        }

        $user = $request->user();
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $favorites = Favorite::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->with([
                'post.user.role',
                'post.media',
            ])
            ->paginate($perPage);

        $posts = collect($favorites->items())
            ->map(function ($favorite) use ($user) {
                $post = $favorite->post;
                if (!$post) {
                    return null;
                }

                $post->loadCount(['likes', 'comments', 'favorites']);
                $post->setAttribute('liked_by_me', $post->likes()->where('user_id', $user->id)->exists());
                $post->setAttribute('favorited_by_me', true);
                $post->setAttribute('favorited_at', $favorite->created_at);

                return $post;
            })
            ->filter()
            ->values();

        return response()->json([
            'message' => 'Favorites fetched successfully',
            'data' => $posts,
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }
}
