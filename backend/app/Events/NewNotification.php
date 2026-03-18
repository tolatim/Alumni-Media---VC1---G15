<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public string $message,
        public string $type,
        public ?int $related_id = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'title'      => $this->title,
            'message'    => $this->message,
            'type'       => $this->type,
            'related_id' => $this->related_id,
        ];
    }
}