<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Keyword;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SmsProcessingService;

class SimulationController extends Controller
{
    protected $smsService;

    public function __construct(SmsProcessingService $smsService)
    {
        $this->smsService = $smsService;
    }
    /**
     * Simulate receiving an SMS from a consumer.
     */
    public function receive(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'content' => 'required|string',
        ]);

        $result = $this->smsService->processIncomingMessage(
            $validated['phone_number'],
            $validated['content']
        );

        return response()->json([
            'message' => 'Simulation successful',
            'incoming' => $result['incoming'],
            'auto_reply' => $result['auto_reply'],
            'keyword_matched' => $result['keyword_matched']
        ], 201);
    }

    /**
     * Get the conversation history for a specific contact.
     */
    public function history($contactId)
    {
        $messages = Message::whereHas('recipients', function ($query) use ($contactId) {
            $query->where('contact_id', $contactId);
        })
        ->with(['recipients.contact'])
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }
}
