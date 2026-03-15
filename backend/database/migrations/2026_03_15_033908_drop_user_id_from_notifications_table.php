<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Add new UUID column
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('new_id')->nullable();
        });

        // 2️⃣ Fill UUID column for existing rows
        DB::table('notifications')->update(['new_id' => DB::raw('UUID()')]);

        // 3️⃣ Drop foreign keys/indexes if any on old id (usually none)

        // 4️⃣ Drop old primary key and column
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropPrimary(); // DO NOT pass 'id'
            $table->dropColumn('id');
        });

        // 5️⃣ Rename new_id → id and make it primary
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('new_id')->primary()->change();
            $table->renameColumn('new_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->bigIncrements('old_id')->first();
            $table->dropPrimary();
            $table->dropColumn('id');
            $table->renameColumn('old_id', 'id');
        });
    }

};