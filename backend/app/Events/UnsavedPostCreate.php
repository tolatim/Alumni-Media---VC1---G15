<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostUnsaved implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $postId,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}.saved-posts")];
    }

    public function broadcastAs(): string
    {
        return 'post.unsaved';
    }

    public function broadcastWith(): array
    {
        return ['post_id' => $this->postId];
    }
}