<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Post;
use App\Models\User;
use App\Notifications\LikePostNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_creates_database_notification_for_post_owner(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'title' => 'Hello', 'content' => 'World']);

        Sanctum::actingAs($actor);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertCreated()
            ->assertJsonPath('liked', true);

        $notification = $owner->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('like_post', $notification->data['notification_type']);
        $this->assertSame($actor->id, $notification->data['actor_id']);
        $this->assertSame($post->id, $notification->data['post_id']);
    }

    public function test_notifications_endpoint_lists_notifications_and_marks_them_read(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'title' => 'Hello', 'content' => 'World']);

        $owner->notify(new LikePostNotification($actor, $post->id));
        $notification = $owner->fresh()->notifications()->firstOrFail();

        Sanctum::actingAs($owner);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.data.notification_type', 'like_post');

        $this->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_connection_request_creates_database_notification_for_target_user(): void
    {
        $requester = User::factory()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($requester);

        $this->postJson('/api/connections/request', ['user_id' => $target->id])
            ->assertCreated();

        $notification = $target->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('connection_request', $notification->data['notification_type']);
        $this->assertSame($requester->id, $notification->data['actor_id']);
    }

    public function test_new_post_creates_notifications_for_connections(): void
    {
        $author = User::factory()->create();
        $friend = User::factory()->create();

        Connection::create([
            'requester_id' => $author->id,
            'addressee_id' => $friend->id,
            'status' => 'accepted',
        ]);

        Sanctum::actingAs($author);

        $response = $this->postJson('/api/posts', [
            'title' => 'Hello',
            'content' => 'World',
        ])->assertCreated();

        $postId = $response->json('post.id');
        $notification = $friend->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('new_post', $notification->data['notification_type']);
        $this->assertSame($author->id, $notification->data['actor_id']);
        $this->assertSame($postId, $notification->data['post_id']);
    }
}
