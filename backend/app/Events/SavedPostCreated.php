<?php

namespace App\Events;

use App\Models\SavedPost;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostSaved implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public SavedPost $savedPost) {}

    public function broadcastOn(): array
    {
        // Only the user who saved it can receive this
        return [new PrivateChannel("user.{$this->savedPost->user_id}.saved-posts")];
    }

    public function broadcastAs(): string
    {
        return 'post.saved';
    }

    public function broadcastWith(): array
    {
        return [
            'saved_post' => [
                'id'         => $this->savedPost->id,
                'post_id'    => $this->savedPost->post_id,
                'user_id'    => $this->savedPost->user_id,
                'created_at' => $this->savedPost->created_at,
                // load the post relation so frontend gets full post data
                'post'       => $this->savedPost->load('post')->post,
            ]
        ];
    }
}