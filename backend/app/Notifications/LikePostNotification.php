<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LikePostNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected User $actor,
        protected int $postId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'notification_type' => 'like_post',
            'message' => $this->actor->name . ' liked your post.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'post_id' => $this->postId,
        ];
    }

    public function toBroadcast(object $notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}