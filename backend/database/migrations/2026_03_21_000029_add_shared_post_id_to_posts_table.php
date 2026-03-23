<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('posts')) return;
        if (Schema::hasColumn('posts', 'shared_post_id')) return;

        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('shared_post_id')
                ->nullable()
                ->after('user_id');
            $table->index('shared_post_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('posts')) return;
        if (Schema::hasColumn('posts', 'shared_post_id')) return;

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['shared_post_id']);
            $table->dropColumn('shared_post_id');
        });
    }
};
