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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('headline')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('bio')->nullable();
            $table->text('skills')->nullable();
            $table->string('avatar')->nullable();
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('graduate_year')->nullable();
            $table->string('current_job')->nullable();
            $table->string('company')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
