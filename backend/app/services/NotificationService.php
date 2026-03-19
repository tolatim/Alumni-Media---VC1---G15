<?php

namespace App\Services;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    public static function send(int $userId, string $title, string $message, string $type, ?int $relatedId = null): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_id' => $relatedId,
        ]);

        try {
            broadcast(new NewNotification($userId, $title, $message, $type, $relatedId));
        } catch (Throwable $exception) {
            Log::warning('Notification broadcast failed.', [
                'user_id' => $userId,
                'type' => $type,
                'related_id' => $relatedId,
                'error' => $exception->getMessage(),
            ]);
        }

        return $notification;
    }

    public static function liked(User $postOwner, User $liker, Post $post): ?Notification
    {
        if ((int) $postOwner->id === (int) $liker->id) {
            return null;
        }

        return self::send(
            $postOwner->id,
            'New Like',
            $liker->name . ' liked your post.',
            'like',
            $post->id
        );
    }

    public static function commented(User $postOwner, User $commenter, Post $post): ?Notification
    {
        if ((int) $postOwner->id === (int) $commenter->id) {
            return null;
        }

        return self::send(
            $postOwner->id,
            'New Comment',
            $commenter->name . ' commented on your post.',
            'comment',
            $post->id
        );
    }

    public static function notifyPostCommented(Post $post, User $commenter): ?Notification
    {
        $post->loadMissing('user');

        if (!$post->user) {
            return null;
        }

        return self::commented($post->user, $commenter, $post);
    }

    public static function connectionRequest(User $receiver, User $sender): Notification
    {
        return self::send(
            $receiver->id,
            'Connection Request',
            $sender->name . ' sent you a connection request.',
            'connect',
            $sender->id
        );
    }

    public static function connectionAccepted(User $requester, User $accepter): Notification
    {
        return self::send(
            $requester->id,
            'Connection Accepted',
            $accepter->name . ' accepted your connection request.',
            'accept',
            $accepter->id
        );
    }

    public static function connectionRejected(User $requester, User $rejecter): Notification
    {
        return self::send(
            $requester->id,
            'Connection Rejected',
            $rejecter->name . ' rejected your connection request.',
            'reject',
            $rejecter->id
        );
    }

    public static function welcome(User $user): Notification
    {
        return self::send(
            $user->id,
            'Welcome!',
            'Welcome to Alumni Media, ' . $user->name . '!',
            'system'
        );
    }

    public static function login(User $user): Notification
    {
        return self::send(
            $user->id,
            'New Login',
            'A new login to your account was detected.',
            'login'
        );
    }
}
