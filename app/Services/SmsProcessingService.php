<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Keyword;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class SmsProcessingService
{
    /**
     * Process an incoming SMS message.
     * Identifies the contact, handles keywords, and triggers auto-replies.
     *
     * @param string $phoneNumber The sender's phone number
     * @param string $content The text message content
     * @return array Result containing the processed message models
     */
    public function processIncomingMessage(string $phoneNumber, string $content)
    {
        return DB::transaction(function () use ($phoneNumber, $content) {
            // 1. Find or create the contact
            $contact = Contact::firstOrCreate(
                ['phone_number' => $phoneNumber],
                ['name' => 'Consumer ' . substr($phoneNumber, -4)]
            );

            // 2. Create the incoming message record
            $incomingMessage = Message::create([
                'content' => $content,
                'type' => 'incoming',
                'user_id' => null, // Incoming messages don't have an admin user_id
            ]);

            // Associate with contact via recipient record
            $incomingMessage->recipients()->create([
                'contact_id' => $contact->id,
                'status' => 'sent', // Mark as received from them
            ]);

            // 3. Engine: Check for Keyword Match (Context-Aware)
            $normalizedContent = strtolower(trim($content));
            $keywordMatch = null;

            // Try matching against sub-keywords of the last used keyword
            if ($contact->last_keyword_id) {
                $keywordMatch = Keyword::where('is_active', true)
                    ->where('parent_id', $contact->last_keyword_id)
                    ->whereRaw('LOWER(keyword) = ?', [$normalizedContent])
                    ->first();
            }

            // If no contextual match, try global/top-level match
            if (!$keywordMatch) {
                $keywordMatch = Keyword::where('is_active', true)
                    ->whereNull('parent_id')
                    ->whereRaw('LOWER(keyword) = ?', [$normalizedContent])
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

            return [
                'incoming' => $incomingMessage->load('recipients.contact'),
                'auto_reply' => $autoReply ? $autoReply->load('recipients.contact') : null,
                'keyword_matched' => (bool)$keywordMatch
            ];
        });
    }
}
