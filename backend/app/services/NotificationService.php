<?php

namespace App\Services;

use App\Events\NewNotification;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationService
{
    public static function welcome(User $user): ?Notification
    {
        return self::send(
            $user->id,
            'Welcome',
            'Welcome to Alumni Media.',
            'system'
        );
    }

    public static function login(User $user): ?Notification
    {
        return self::send(
            $user->id,
            'Login',
            'You logged in successfully.',
            'login'
        );
    }

    public static function liked(User $receiver, User $actor, Post $post): ?Notification
    {
        if ((int) $receiver->id === (int) $actor->id) {
            return null;
        }

        return self::send(
            $receiver->id,
            'New Like',
            trim($actor->first_name . ' ' . $actor->last_name) . ' liked your post.',
            'like',
            $post->id
        );
    }

    public static function notifyPostCommented(Post $post, User $actor): ?Notification
    {
        $receiver = $post->user;
        if (!$receiver || (int) $receiver->id === (int) $actor->id) {
            return null;
        }

        return self::send(
            $receiver->id,
            'New Comment',
            trim($actor->first_name . ' ' . $actor->last_name) . ' commented on your post.',
            'comment',
            $post->id
        );
    }

    public static function connectionRequest(?User $receiver, User $actor): ?Notification
    {
        if (!$receiver || (int) $receiver->id === (int) $actor->id) {
            return null;
        }

        return self::send(
            $receiver->id,
            'Connection Request',
            trim($actor->first_name . ' ' . $actor->last_name) . ' sent you a connection request.',
            'connect',
            $actor->id
        );
    }

    public static function connectionAccepted(?User $receiver, User $actor): ?Notification
    {
        if (!$receiver || (int) $receiver->id === (int) $actor->id) {
            return null;
        }

        return self::send(
            $receiver->id,
            'Connection Accepted',
            trim($actor->first_name . ' ' . $actor->last_name) . ' accepted your connection request.',
            'accept',
            $actor->id
        );
    }

    public static function connectionRejected(?User $receiver, User $actor): ?Notification
    {
        if (!$receiver || (int) $receiver->id === (int) $actor->id) {
            return null;
        }

        return self::send(
            $receiver->id,
            'Connection Rejected',
            trim($actor->first_name . ' ' . $actor->last_name) . ' rejected your connection request.',
            'reject',
            $actor->id
        );
    }

    public static function send(
        int $userId,
        string $title,
        string $message,
        string $type,
        ?int $relatedId = null
    ): ?Notification {
        if (!self::canPersistNotifications()) {
            return null;
        }

        try {
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'related_id' => $relatedId,
                'read_at' => null,
            ]);

            self::broadcast($notification);

            return $notification;
        } catch (Throwable) {
            return null;
        }
    }

    private static function broadcast(Notification $notification): void
    {
        try {
            broadcast(new NewNotification(
                (int) $notification->user_id,
                (string) $notification->title,
                (string) $notification->message,
                (string) $notification->type,
                $notification->related_id ? (int) $notification->related_id : null
            ))->toOthers();
        } catch (Throwable) {
            // Keep notifications non-blocking even if broadcast transport is unavailable.
        }
    }

    private static function canPersistNotifications(): bool
    {
        return Schema::hasTable('notifications')
            && Schema::hasColumns('notifications', [
                'user_id',
                'title',
                'message',
                'type',
                'related_id',
                'read_at',
            ]);
    }
}
