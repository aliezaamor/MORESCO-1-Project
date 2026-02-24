<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Keyword;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimulationController extends Controller
{
    /**
     * Simulate receiving an SMS from a consumer.
     */
    public function receive(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'content' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Find or create the contact
            $contact = Contact::firstOrCreate(
                ['phone_number' => $validated['phone_number']],
                ['name' => 'Consumer ' . substr($validated['phone_number'], -4)]
            );

            // 2. Create the incoming message record
            $incomingMessage = Message::create([
                'content' => $validated['content'],
                'type' => 'incoming',
                'user_id' => null, // Incoming messages don't have an admin user_id
            ]);

            // Associate with contact via recipient record (in this case, system is the recipient)
            $incomingMessage->recipients()->create([
                'contact_id' => $contact->id,
                'status' => 'sent', // Mark as received from them
            ]);

            // 3. Engine: Check for Keyword Match (Context-Aware)
            $content = strtolower(trim($validated['content']));
            $keywordMatch = null;

            // Try matching against sub-keywords of the last used keyword
            if ($contact->last_keyword_id) {
                $keywordMatch = Keyword::where('is_active', true)
                    ->where('parent_id', $contact->last_keyword_id)
                    ->whereRaw('LOWER(keyword) = ?', [$content])
                    ->first();
            }

            // If no contextual match, try global/top-level match
            if (!$keywordMatch) {
                $keywordMatch = Keyword::where('is_active', true)
                    ->whereNull('parent_id')
                    ->whereRaw('LOWER(keyword) = ?', [$content])
                    ->first();
            }

            $autoReply = null;
            if ($keywordMatch) {
                // 4. Update context
                // Only keep context if this keyword has children (it's a menu)
                $hasChildren = Keyword::where('parent_id', $keywordMatch->id)->exists();
                $contact->update(['last_keyword_id' => $hasChildren ? $keywordMatch->id : null]);

                // 5. Trigger Auto-reply
                $autoReply = Message::create([
                    'content' => $keywordMatch->reply_content,
                    'type' => 'auto_reply',
                    'user_id' => null,
                ]);

                $autoReply->recipients()->create([
                    'contact_id' => $contact->id,
                    'status' => 'sent',
                ]);
            } else {
                // Reset context if no keyword matches at all
                $contact->update(['last_keyword_id' => null]);
            }

            return response()->json([
                'message' => 'Simulation successful',
                'incoming' => $incomingMessage->load('recipients.contact'),
                'auto_reply' => $autoReply ? $autoReply->load('recipients.contact') : null,
                'keyword_matched' => (bool)$keywordMatch
            ], 201);
        });
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
