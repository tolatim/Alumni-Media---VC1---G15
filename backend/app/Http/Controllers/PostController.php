<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user.profile', 'media'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Feed fetched successfully',
            'data' => $posts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_content' => 'required|string|max:5000',
        ]);

        $post = $request->user()->posts()->create([
            'post_content' => $validated['post_content'],
        ]);

        $post->load(['user.profile', 'media'])->loadCount(['likes', 'comments']);

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post,
        ], 201);
    }
}
