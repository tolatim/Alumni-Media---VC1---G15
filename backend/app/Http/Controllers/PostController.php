<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        return Post::with('user')->latest()->get();
    }

    // store post
    public function store(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);


        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post
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
        
        if (!$post){
            return response()->json(['message'=>'Post not found',404]);
        }

        $request->validate([
            'content' => 'required|string'
        ]);

        $post->update([
            'content'=> $request->content,
        ]);

        return response()->json([
            'message'=> 'Post updated successfully',
            'post'=> $post
        ]);

    }

    // delete post
    public function destroy($id)
    {
        $post = Post::find($id);
        if(!$post){
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post -> delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
