<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ConnectionNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected User $actor,
        protected int $connectionId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'notification_type' => 'connection_request',
            'message' => $this->actor->name . ' sent you a connection request.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'connection_id' => $this->connectionId,
        ];
    }

    public function toBroadcast(object $notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}