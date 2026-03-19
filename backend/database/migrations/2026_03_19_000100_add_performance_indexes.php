<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['receiver_id', 'read_at'], 'messages_receiver_read_at_index');
            $table->index(['receiver_id', 'sender_id', 'read_at'], 'messages_receiver_sender_read_at_index');
            $table->index(['sender_id', 'receiver_id', 'id'], 'messages_sender_receiver_id_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'notifications_user_read_at_index');
            $table->index(['user_id', 'created_at'], 'notifications_user_created_at_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'posts_user_created_at_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'created_at'], 'comments_post_created_at_index');
            $table->index(['user_id', 'created_at'], 'comments_user_created_at_index');
        });

        Schema::table('connections', function (Blueprint $table) {
            $table->index(['requester_id', 'status'], 'connections_requester_status_index');
            $table->index(['addressee_id', 'status', 'created_at'], 'connections_addressee_status_created_at_index');
            $table->index(['requester_id', 'status', 'created_at'], 'connections_requester_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_receiver_read_at_index');
            $table->dropIndex('messages_receiver_sender_read_at_index');
            $table->dropIndex('messages_sender_receiver_id_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_at_index');
            $table->dropIndex('notifications_user_created_at_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_user_created_at_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_created_at_index');
            $table->dropIndex('comments_user_created_at_index');
        });

        Schema::table('connections', function (Blueprint $table) {
            $table->dropIndex('connections_requester_status_index');
            $table->dropIndex('connections_addressee_status_created_at_index');
            $table->dropIndex('connections_requester_status_created_at_index');
        });
    }
};
