<?php

namespace App\Support;

use App\Models\Notification;

class NotificationService
{
    public static function create(int $userId, string $type, array $data = []): ?Notification
    {
        if ($userId <= 0) {
            return null;
        }

        try {
            $notification = Notification::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'data' => $data,
                'seen' => false,
            ]);

            $notificationPayload = $notification->fresh()?->toArray() ?? $notification->toArray();

            WebsocketNotifier::send('notification_created', [
                'target_user_id' => $userId,
                'notification' => $notificationPayload,
            ]);

            return $notification;
        } catch (\Throwable $exception) {
            \Log::warning('Failed to create notification: ' . $exception->getMessage());
            return null;
        }
    }
}
