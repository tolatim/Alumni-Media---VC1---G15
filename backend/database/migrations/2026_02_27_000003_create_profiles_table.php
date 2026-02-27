<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('graduate_year')->nullable();
            $table->string('current_job')->nullable();
            $table->string('company')->nullable();
            $table->timestamps();
            $table->index('graduate_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
