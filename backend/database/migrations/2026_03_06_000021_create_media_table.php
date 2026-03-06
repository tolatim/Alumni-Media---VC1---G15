<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media')) {
            return;
        }

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('type')->default('image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('media')) {
            return;
        }

        Schema::dropIfExists('media');
    }
};
