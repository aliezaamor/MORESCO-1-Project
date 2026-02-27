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
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_scheduled')->default(false)->after('type');
            $table->timestamp('scheduled_at')->nullable()->after('is_scheduled');
            $table->boolean('no_reply')->default(true)->after('scheduled_at');
            $table->enum('category', ['MCO CONTACTS', 'ADVISORY', 'OUTAGE', 'EVENTS'])->default('ADVISORY')->after('no_reply');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['is_scheduled', 'scheduled_at', 'no_reply', 'category']);
        });
    }
};
