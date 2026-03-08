<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $comments = Comment::query()
            ->with('user')
            ->where('post_id', $post->id)
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Comments fetched successfully',
            'data' => $comments,
            'comments_count' => $comments->count(),
        ]);
    }

    public function store(Request $request, $postId)
    {
        $user = $request->user();
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => trim($validated['content']),
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load('user'),
            'comments_count' => $post->comments()->count(),
        ], 201);
    }

    public function destroy(Request $request, $commentId)
    {
        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        if ((int) $comment->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'You can delete only your own comments.'], 403);
        }

        $post = Post::find($comment->post_id);
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
            'comments_count' => $post ? $post->comments()->count() : 0,
        ]);
    }

    public function update(Request $request, $commentId)
    {
        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        if ((int) $comment->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'You can edit only your own comments.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $comment->update([
            'content' => trim($validated['content']),
        ]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment->fresh()->load('user'),
        ]);
    }
}
