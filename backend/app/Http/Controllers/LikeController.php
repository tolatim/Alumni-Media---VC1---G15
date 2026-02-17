<?php

namespace App\Http\Controllers;

use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Like::with(['user', 'post'])->latest('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'post_id' => ['required', 'exists:posts,id'],
        ]);

        $like = Like::create($validated);

        return response()->json($like->load(['user', 'post']), 201);
    }

    public function show(Like $like): JsonResponse
    {
        return response()->json($like->load(['user', 'post']));
    }

    public function destroy(Like $like): JsonResponse
    {
        $like->delete();

        return response()->json(status: 204);
    }
}
