<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')
            ->latest()
            ->paginate(5);

        return response()->json($posts);
    }

    // store post
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string',
            'content' => 'nullable|string',
        ]);

        $post = Post::create([
            'user_id' => auth()->id(), // safer
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json([
            "message" => "Post successfully",
            "post" => $post
        ], 201);
    }

    // show post
    public function show($id)
    {
        $post = Post::with('user')->findOrFail($id);

        return response()->json($post);
    }

    // update post
    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found', 404]);
        }

        $request->validate([
            'content' => 'required|string'
        ]);

        $post->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post
        ]);
    }

    // delete post
    public function destroy($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
