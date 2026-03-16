<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SavedPost;



class SavedPostController extends Controller
{
    //
    public function save($postId)
    {
        SavedPost::firstOrCreate([
            'user_id' => auth()->id(),
            'post_id' => $postId
        ]);

        return response()->json([
            'message' => 'Post saved'
        ]);
    }

    public function unsave($postId)
    {
        SavedPost::where('user_id', auth()->id())
            ->where('post_id', $postId)
            ->delete();

        return response()->json([
            'message' => 'Post removed'
        ]);
    }

    public function index()
    {
        $saved = SavedPost::where('user_id', auth()->id())
            ->pluck('post_id');

        return response()->json($saved);
    }
}
