<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Save;
use Illuminate\Http\Request;

class SaveController extends Controller
{
    public function toggle(Request $request, $postId)
    {
        $user = $request->user();
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $existing = Save::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'message' => 'Post removed from saved items',
                'saved' => false,
                'saves_count' => $post->saves()->count(),
            ]);
        }

        Save::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Post saved successfully',
            'saved' => true,
            'saves_count' => $post->saves()->count(),
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $posts = Post::with(['user', 'media'])
            ->withCount([
                'likes',
                'comments',
                'saves',
                'likes as liked_by_me' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
                'saves as saved_by_me' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->whereHas('saves', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        $posts->getCollection()->transform(function ($post) {
            $post->liked_by_me = (bool) ($post->liked_by_me ?? false);
            $post->saved_by_me = (bool) ($post->saved_by_me ?? false);
            return $post;
        });

        return response()->json($posts);
    }
}