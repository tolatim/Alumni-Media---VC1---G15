<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Support\NotificationService;
use App\Support\WebsocketNotifier;
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
            ->with(['user', 'replies'])
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Comments fetched successfully',
            'data' => $comments,
            'comments_count' => $post->comments()->count(),
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
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $parentComment = null;
        if ($parentId) {
            $parentComment = Comment::query()
                ->where('id', $parentId)
                ->where('post_id', $post->id)
                ->first();

            if (!$parentComment) {
                return response()->json(['message' => 'Parent comment not found for this post.'], 422);
            }
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => trim($validated['content']),
        ]);

        $commentsCount = $post->comments()->count();
        WebsocketNotifier::send('post_comment_updated', [
            'post_id' => $post->id,
            'comment_id' => $comment->id,
            'comments_count' => $commentsCount,
            'action' => $parentId ? 'reply_created' : 'comment_created',
        ]);

        $actorName = $user->name;

        if ($parentComment) {
            if ((int) $parentComment->user_id !== (int) $user->id) {
                NotificationService::create((int) $parentComment->user_id, 'post_reply', [
                    'actor_user_id' => $user->id,
                    'actor_name' => $actorName,
                    'post_id' => $post->id,
                    'post_owner_id' => (int) $post->user_id,
                    'comment_id' => $comment->id,
                    'parent_comment_id' => $parentComment->id,
                    'message' => "{$actorName} replied to your comment.",
                ]);
            }

            if ((int) $post->user_id !== (int) $user->id && (int) $post->user_id !== (int) $parentComment->user_id) {
                NotificationService::create((int) $post->user_id, 'post_comment', [
                    'actor_user_id' => $user->id,
                    'actor_name' => $actorName,
                    'post_id' => $post->id,
                    'post_owner_id' => (int) $post->user_id,
                    'comment_id' => $comment->id,
                    'message' => "{$actorName} commented on your post.",
                ]);
            }
        } elseif ((int) $post->user_id !== (int) $user->id) {
            NotificationService::create((int) $post->user_id, 'post_comment', [
                'actor_user_id' => $user->id,
                'actor_name' => $actorName,
                'post_id' => $post->id,
                'post_owner_id' => (int) $post->user_id,
                'comment_id' => $comment->id,
                'message' => "{$actorName} commented on your post.",
            ]);
        }

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load('user'),
            'comments_count' => $commentsCount,
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
        $deletedCommentId = $comment->id;
        $comment->delete();

        $commentsCount = $post ? $post->comments()->count() : 0;
        WebsocketNotifier::send('post_comment_updated', [
            'post_id' => $comment->post_id,
            'comment_id' => $deletedCommentId,
            'comments_count' => $commentsCount,
            'action' => 'comment_deleted',
        ]);

        return response()->json([
            'message' => 'Comment deleted successfully',
            'comments_count' => $commentsCount,
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

        $post = Post::find($comment->post_id);
        WebsocketNotifier::send('post_comment_updated', [
            'post_id' => $comment->post_id,
            'comment_id' => $comment->id,
            'comments_count' => $post ? $post->comments()->count() : 0,
            'action' => 'comment_updated',
        ]);

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment->fresh()->load('user'),
        ]);
    }
}
