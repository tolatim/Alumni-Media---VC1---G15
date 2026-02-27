<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Models\PostUser;

class PostController extends Controller
{
    public function index()
    {
        return PostUser::with('user')->latest()->get();
    }

    public function store(Request $request)
    {
       $request->validate([
            'content' => 'required|string',
            'image'=> 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
       ]);

       $imagePath = null ;
       
       if ($request-> hasFile('image')){
          $imagePath = $request->file(('image'))->store('posts','public');
       }
       
       $post = PostUser::create([
        'user_id'=> auth()->id(),
        'content' => $request->content,
        'image' => $imagePath,
       ]);
       return response()->json($post, 201);
    }

    public function show(Post $post)
    {
        $post = PostUser::with('user')->findOrFail($id);
        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        // Logic to update a specific post
    }

    public function destroy($id)
    {
        // Logic to delete a specific post
    }
}
