<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('messages', ['receiver_id', 'read_at'], 'messages_receiver_read_at_index');
        $this->addIndexIfMissing('messages', ['receiver_id', 'sender_id', 'read_at'], 'messages_receiver_sender_read_at_index');
        $this->addIndexIfMissing('messages', ['sender_id', 'receiver_id', 'id'], 'messages_sender_receiver_id_index');

        $this->addIndexIfMissing('posts', ['user_id', 'created_at'], 'posts_user_created_at_index');

        $this->addIndexIfMissing('comments', ['post_id', 'created_at'], 'comments_post_created_at_index');
        $this->addIndexIfMissing('comments', ['user_id', 'created_at'], 'comments_user_created_at_index');

        $this->addIndexIfMissing('connections', ['requester_id', 'status'], 'connections_requester_status_index');
        $this->addIndexIfMissing('connections', ['addressee_id', 'status', 'created_at'], 'connections_addressee_status_created_at_index');
        $this->addIndexIfMissing('connections', ['requester_id', 'status', 'created_at'], 'connections_requester_status_created_at_index');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('messages', 'messages_receiver_read_at_index');
        $this->dropIndexIfExists('messages', 'messages_receiver_sender_read_at_index');
        $this->dropIndexIfExists('messages', 'messages_sender_receiver_id_index');

        $this->dropIndexIfExists('posts', 'posts_user_created_at_index');

        $this->dropIndexIfExists('comments', 'comments_post_created_at_index');
        $this->dropIndexIfExists('comments', 'comments_user_created_at_index');

        $this->dropIndexIfExists('connections', 'connections_requester_status_index');
        $this->dropIndexIfExists('connections', 'connections_addressee_status_created_at_index');
        $this->dropIndexIfExists('connections', 'connections_requester_status_created_at_index');
    }

    private function addIndexIfMissing(string $tableName, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($tableName) || $this->hasIndex($tableName, $indexName) || !$this->hasColumns($tableName, $columns)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!Schema::hasTable($tableName) || !$this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();
        $result = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();

        return $result;
    }

    private function hasColumns(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }
};
