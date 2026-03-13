<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Post;

class NotificationService
{
    /**
     * Create a notification for when a post is liked
     */
    public static function notifyPostLiked(Post $post, User $liker)
    {
        // Don't notify the user about their own likes
        if ($post->user_id === $liker->id) {
            return;
        }

        Notification::create([
            'notifiable_id' => $post->user_id,
            'notifiable_type' => User::class,
            'type' => 'like',
            'data' => [
                'user_id' => $liker->id,
                'user_name' => $liker->first_name . ' ' . $liker->last_name,
                'user_avatar' => $liker->avatar,
                'post_id' => $post->id,
                'message' => $liker->first_name . ' ' . $liker->last_name . ' liked your post',
            ],
        ]);
    }

    /**
     * Create a notification for when a post is commented on
     */
    public static function notifyPostCommented(Post $post, User $commenter)
    {
        // Don't notify the user about their own comments
        if ($post->user_id === $commenter->id) {
            return;
        }

        Notification::create([
            'user_id' => $post->user_id,
            'type' => 'comment',
            'notifiable_id' => $post->id,
            'notifiable_type' => Post::class,
            'data' => [
                'user_id' => $commenter->id,
                'user_name' => $commenter->first_name . ' ' . $commenter->last_name,
                'user_avatar' => $commenter->avatar,
                'post_id' => $post->id,
                'message' => $commenter->first_name . ' ' . $commenter->last_name . ' commented on your post',
            ],
        ]);
    }

    /**
     * Create a notification for a new follower
     */
    public static function notifyNewFollower(User $followedUser, User $follower)
    {
        Notification::create([
            'user_id' => $followedUser->id,
            'type' => 'follow',
            'notifiable_id' => $follower->id,
            'notifiable_type' => User::class,
            'data' => [
                'user_id' => $follower->id,
                'user_name' => $follower->first_name . ' ' . $follower->last_name,
                'user_avatar' => $follower->avatar,
                'message' => $follower->first_name . ' ' . $follower->last_name . ' started following you',
            ],
        ]);
    }

    /**
     * Create a notification for a new message
     */
    public static function notifyNewMessage(User $receiver, User $sender, $messageContent)
    {
        Notification::create([
            'user_id' => $receiver->id,
            'type' => 'message',
            'notifiable_id' => $sender->id,
            'notifiable_type' => User::class,
            'data' => [
                'user_id' => $sender->id,
                'user_name' => $sender->first_name . ' ' . $sender->last_name,
                'user_avatar' => $sender->avatar,
                'message' => 'New message from ' . $sender->first_name . ' ' . $sender->last_name,
                'content_preview' => substr($messageContent, 0, 50) . (strlen($messageContent) > 50 ? '...' : ''),
            ],
        ]);
    }

    /**
     * Delete notifications of a specific type for a post
     */
    public static function deleteNotificationsForPost(Post $post, $type = null)
    {
        $query = Notification::where('notifiable_id', $post->id)
            ->where('notifiable_type', Post::class);

        if ($type) {
            $query->where('type', $type);
        }

        $query->delete();
    }
}
