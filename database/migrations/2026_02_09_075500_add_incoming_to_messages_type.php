<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run raw ALTER for MySQL — SQLite doesn't support MODIFY
        if (DB::getDriverName() === 'mysql') {
            Schema::table('messages', function (Blueprint $table) {
                // Using raw statement as changing enum types can be tricky
                DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('individual', 'broadcast', 'auto_reply', 'incoming') NOT NULL");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            //
        });
    }
};
