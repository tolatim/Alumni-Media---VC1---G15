<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            $table->unsignedBigInteger('notifiable_id')
                    ->nullable();
            $table->string('notifiable_type')
                    ->nullable();
            $table->string('type');
            $table->json('data')
                    ->nullable();
            $table->timestamp('read_at')
                    ->nullable();
            $table->timestamp('created_at')
                    ->useCurrent();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
