<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->timestamp('window_start')->nullable(); // Start of current rolling window
            $table->unsignedInteger('message_count')->default(0);
            $table->boolean('is_warned')->default(false);
            $table->boolean('is_throttled')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique('contact_id'); // One row per contact
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_rate_limits');
    }
};
