<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Post::with('user')->latest('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'content' => ['required', 'string'],
            'media_id' => ['nullable', 'integer'],
        ]);

        $post = Post::create($validated);

        return response()->json($post->load('user'), 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json($post->load('user'));
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'exists:users,id'],
            'content' => ['sometimes', 'string'],
            'media_id' => ['nullable', 'integer'],
        ]);

        $post->update($validated);

        return response()->json($post->load('user'));
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json(status: 204);
    }
}
