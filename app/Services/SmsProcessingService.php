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
            // Normalize phone number to match based on the last 10 digits (ignoring prefixes like +63 or 0)
            $normalizedNumber = strlen($phoneNumber) >= 10 ? substr($phoneNumber, -10) : $phoneNumber;
            
            $contact = Contact::where('phone_number', 'like', '%' . $normalizedNumber)->first();

            if (!$contact) {
                $contact = Contact::create([
                    'phone_number' => $phoneNumber,
                    'name' => 'Consumer ' . substr($phoneNumber, -4)
                ]);
            }

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

                // 5. Trigger Auto-reply based on action_type
                $replyContent = $keywordMatch->reply_content;
                $actionType = $keywordMatch->action_type;
                $actionData = $keywordMatch->action_data ?? [];

                switch ($actionType) {
                    case 'billing_info':
                        // TODO: Connect to MORESCO external system here
                        $hasOutstandingBalance = true; // Placeholder logic
                        $replyContent = $hasOutstandingBalance 
                            ? ($actionData['has_balance'] ?? $replyContent) 
                            : ($actionData['no_balance'] ?? $replyContent);
                        break;
                        
                    case 'due_date_info':
                        // TODO: Connect to MORESCO external system here
                        $hasDueDate = true; // Placeholder logic
                        $replyContent = $hasDueDate 
                            ? ($actionData['has_due'] ?? $replyContent) 
                            : ($actionData['settled'] ?? $replyContent);
                        break;
                        
                    case 'payment_history':
                        // TODO: Connect to MORESCO external system here
                        $recordFound = true; // Placeholder logic
                        $replyContent = $recordFound 
                            ? ($actionData['record_found'] ?? $replyContent) 
                            : ($actionData['no_record'] ?? $replyContent);
                        break;
                        
                    case 'account_status':
                        // TODO: Connect to MORESCO external system here
                        $status = 'active'; // Placeholder logic: 'active', 'for_disconnection', 'disconnected'
                        if ($status === 'active') $replyContent = $actionData['active'] ?? $replyContent;
                        elseif ($status === 'for_disconnection') $replyContent = $actionData['for_disconnection'] ?? $replyContent;
                        else $replyContent = $actionData['disconnected'] ?? $replyContent;
                        break;

                    case 'advisory_info':
                        // TODO: Connect to MORESCO advisory system here
                        $activeAdvisory = true; // Placeholder logic
                        $replyContent = $activeAdvisory 
                            ? ($actionData['active_advisory'] ?? $replyContent) 
                            : ($actionData['no_advisory'] ?? $replyContent);
                        break;

                    case 'outage_report':
                        // TODO: Connect to MORESCO outage system here
                        $outageState = 'request_location'; // Placeholder: 'request_location', 'reported_success', 'invalid_location', 'already_reported'
                        if ($outageState === 'reported_success') $replyContent = $actionData['reported_success'] ?? $replyContent;
                        elseif ($outageState === 'invalid_location') $replyContent = $actionData['invalid_location'] ?? $replyContent;
                        elseif ($outageState === 'already_reported') $replyContent = $actionData['already_reported'] ?? $replyContent;
                        else $replyContent = $actionData['request_location'] ?? $replyContent;
                        break;
                        
                    case 'events_info':
                        // TODO: Connect to MORESCO events system here
                        $hasEvent = true; // Placeholder logic
                        $replyContent = $hasEvent 
                            ? ($actionData['has_event'] ?? $replyContent) 
                            : ($actionData['no_event'] ?? $replyContent);
                        break;

                    case 'static':
                    default:
                        // $replyContent is already set to the default reply_content above.
                        break;
                }

                $autoReply = Message::create([
                    'content' => $replyContent,
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
