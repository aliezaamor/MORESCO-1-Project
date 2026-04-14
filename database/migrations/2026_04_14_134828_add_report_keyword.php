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
        \App\Models\Keyword::create([
            'keyword'       => 'REPORT',
            'reply_content' => "MORESCO-1: To report an outage, please provide the following details: 1. What happened? 2. Where? 3. When? and stay tuned for updates.",
            'is_active'     => true,
            'action_type'   => 'outage_report',
            'action_data'   => [
                'has_outage' => "MORESCO-1: We are aware of the outage in your area ({work_name}). Status: {work_status}. Interruption: {power_interruption}. Location: {location}. Remarks: {remarks}",
                'no_outage'  => "MORESCO-1: No scheduled outage reported for your area. We have logged your report. Please provide the following details: 1. What happened? 2. Where? 3. When?",
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Keyword::where('keyword', 'REPORT')->delete();
    }
};
