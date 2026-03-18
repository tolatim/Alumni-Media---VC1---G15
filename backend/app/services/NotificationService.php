<?php

namespace App\Services;

use App\Events\NewNotification;
use App\Models\Notification;

class NotificationService
{
    public static function send($userId, $title, $message, $type, $relatedId = null)
    {
        // Save to database
        $notification = Notification::create([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'related_id' => $relatedId,
        ]);

        // Broadcast real-time
        broadcast(new NewNotification($userId, $title, $message, $type, $relatedId));

        return $notification;
    }

    public static function liked($postOwner, $liker, $post)
    {
        if ($postOwner->id === $liker->id) return;
        self::send($postOwner->id, 'New Like',
            $liker->name . ' liked your post', 'like', $post->id);
    }

    public static function commented($postOwner, $commenter, $post)
    {
        if ($postOwner->id === $commenter->id) return;
        self::send($postOwner->id, 'New Comment',
            $commenter->name . ' commented on your post', 'comment', $post->id);
    }

    public static function newPost($author, $post)
    {
        foreach ($author->followers as $follower) {
            self::send($follower->id, 'New Post',
                $author->name . ' published a new post', 'post', $post->id);
        }
    }

    public static function connectionRequest($receiver, $sender)
    {
        self::send($receiver->id, 'Connection Request',
            $sender->name . ' sent you a connection request', 'connect', $sender->id);
    }

    public static function connectionAccepted($requester, $accepter)
    {
        self::send($requester->id, 'Connection Accepted',
            $accepter->name . ' accepted your connection request', 'accept', $accepter->id);
    }

    public static function connectionRejected($requester, $rejecter)
    {
        self::send($requester->id, 'Connection Rejected',
            $rejecter->name . ' rejected your connection request', 'reject', $rejecter->id);
    }

    public static function welcome($user)
    {
        self::send($user->id, 'Welcome!',
            'Welcome to Alumni Media, ' . $user->name . '!', 'system');
    }

    public static function login($user)
    {
        self::send($user->id, 'New Login',
            'A new login to your account was detected', 'login');
    }
}