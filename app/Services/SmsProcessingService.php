<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Keyword;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsProcessingService
{
    /**
     * Process an incoming SMS.
     *
     * Format supported:   KEYWORD ACCOUNTNUMBER
     * Examples:           BILL 987987
     *                     STATUS 987987
     *                     HELP
     *
     * @param string $phoneNumber  The sender's phone number
     * @param string $content      The full SMS text
     */
    public function processIncomingMessage(string $phoneNumber, string $content)
    {
        return DB::transaction(function () use ($phoneNumber, $content) {

            // ── 1. Find or create sender contact ─────────────────────────────
            $normalizedNumber = strlen($phoneNumber) >= 10
                ? substr($phoneNumber, -10)
                : $phoneNumber;

            $contact = Contact::where('phone_number', 'like', '%' . $normalizedNumber)->first();
            if (!$contact) {
                $contact = Contact::create([
                    'phone_number' => $phoneNumber,
                    'name'         => 'Consumer ' . substr($phoneNumber, -4),
                ]);
            }

            // ── 2. Store the incoming message ─────────────────────────────────
            $incomingMessage = Message::create([
                'content' => $content,
                'type'    => 'incoming',
                'user_id' => null,
            ]);
            $incomingMessage->recipients()->create([
                'contact_id' => $contact->id,
                'status'     => 'sent',
            ]);

            // ── 3. Parse KEYWORD [ACCOUNT_NUMBER] ────────────────────────────
            //   "BILL 987987"  → keywordText = "bill",  accountNumber = "987987"
            //   "HELP"         → keywordText = "help",  accountNumber = null
            $normalized    = strtolower(trim(preg_replace('/\s+/', ' ', $content)));
            $parts         = explode(' ', $normalized, 2);
            $keywordText   = $parts[0];
            $accountNumber = isset($parts[1]) ? trim($parts[1]) : null;

            // ── 4. Match keyword (context-aware) ──────────────────────────────
            $keywordMatch = null;

            // Try sub-keyword of last used keyword first
            if ($contact->last_keyword_id) {
                $keywordMatch = Keyword::where('is_active', true)
                    ->where('parent_id', $contact->last_keyword_id)
                    ->whereRaw('LOWER(keyword) = ?', [$keywordText])
                    ->first();
            }

            // Fall back to global / top-level keyword
            if (!$keywordMatch) {
                $keywordMatch = Keyword::where('is_active', true)
                    ->whereNull('parent_id')
                    ->whereRaw('LOWER(keyword) = ?', [$keywordText])
                    ->first();
            }

            // ── 5. Build auto-reply ───────────────────────────────────────────
            $autoReply = null;

            if ($keywordMatch) {

                // Update context (keep context only if this keyword has children)
                $hasChildren = Keyword::where('parent_id', $keywordMatch->id)->exists();
                $contact->update(['last_keyword_id' => $hasChildren ? $keywordMatch->id : null]);

                $replyContent = $keywordMatch->reply_content;
                $actionType   = $keywordMatch->action_type;
                $actionData   = $keywordMatch->action_data ?? [];

                // Actions that require an account number
                $needsAccount = in_array($actionType, [
                    'billing_info',
                    'due_date_info',
                    'payment_history',
                    'account_status',
                ]);

                $member = null;

                if ($needsAccount) {
                    if (!$accountNumber) {
                        // Prompt the consumer to include their account number
                        $kw           = strtoupper($keywordText);
                        $replyContent = "MORESCO-1: Please include your account number.\nExample: {$kw} 987654";
                        $actionType   = 'static';
                    } else {
                        // Look up member in MORESCO external DB
                        $morescoService = app(MorescoDbService::class);
                        $member = $morescoService->getMemberByAccountNumber($accountNumber);

                        if (!$member) {
                            $replyContent = "MORESCO-1: Account number '{$accountNumber}' was not found. Please check and try again.";
                            $actionType   = 'static';
                        }
                    }
                }

                // ── Action processing ─────────────────────────────────────────
                switch ($actionType) {

                    case 'billing_info':
                        $status     = strtolower($member['status'] ?? '');
                        $hasBalance = !in_array($status, ['paid', 'settled', 'current']);
                        $replyContent = $hasBalance
                            ? ($actionData['has_balance'] ?? $replyContent)
                            : ($actionData['no_balance']  ?? $replyContent);
                        break;

                    case 'due_date_info':
                        $status = strtolower($member['status'] ?? '');
                        $hasDue = str_contains($status, 'due') || str_contains($status, 'unpaid');
                        $replyContent = $hasDue
                            ? ($actionData['has_due'] ?? $replyContent)
                            : ($actionData['settled']  ?? $replyContent);
                        break;

                    case 'payment_history':
                        $replyContent = $actionData['record_found'] ?? $replyContent;
                        break;

                    case 'account_status':
                        $status = strtolower($member['status'] ?? '');
                        if (str_contains($status, 'active')) {
                            $replyContent = $actionData['active']           ?? $replyContent;
                        } elseif (str_contains($status, 'disconn')) {
                            $replyContent = $actionData['disconnected']     ?? $replyContent;
                        } else {
                            $replyContent = $actionData['for_disconnection'] ?? $replyContent;
                        }
                        break;

                    case 'advisory_info':
                        // TODO: connect to MORESCO advisory system
                        $activeAdvisory = true;
                        $replyContent = $activeAdvisory
                            ? ($actionData['active_advisory'] ?? $replyContent)
                            : ($actionData['no_advisory']     ?? $replyContent);
                        break;

                    case 'outage_report':
                        // TODO: connect to MORESCO outage system
                        $outageState = 'request_location';
                        if ($outageState === 'reported_success')   $replyContent = $actionData['reported_success']   ?? $replyContent;
                        elseif ($outageState === 'invalid_location')  $replyContent = $actionData['invalid_location']  ?? $replyContent;
                        elseif ($outageState === 'already_reported')  $replyContent = $actionData['already_reported']  ?? $replyContent;
                        else                                          $replyContent = $actionData['request_location']  ?? $replyContent;
                        break;

                    case 'events_info':
                        // TODO: connect to MORESCO events system
                        $hasEvent = true;
                        $replyContent = $hasEvent
                            ? ($actionData['has_event'] ?? $replyContent)
                            : ($actionData['no_event']  ?? $replyContent);
                        break;

                    case 'static':
                    default:
                        // $replyContent already set above
                        break;
                }

                // ── Replace {placeholders} with real data ────────────────────
                // Member info placeholders (always available when $member is set)
                if ($member) {
                    $replyContent = str_replace(
                        ['{name}',               '{account}',      '{area}',                     '{status}',              '{municipality}',               '{barangay}'              ],
                        [$member['name'] ?? '',  $accountNumber,   $member['service_area'] ?? '', $member['status'] ?? '', $member['municipality'] ?? '', $member['barangay'] ?? ''],
                        $replyContent
                    );
                }

                // Billing/payment placeholders — fetched for relevant action types
                if ($member && in_array($actionType, ['billing_info', 'due_date_info', 'payment_history'])) {
                    $billing = $morescoService->getMemberBillingData($accountNumber);
                    $replyContent = str_replace(
                        ['{bill_amount}',                   '{billing_period}',                   '{due_date}',                   '{last_payment_amount}',                   '{last_payment_date}',                   '{or_number}'                 ],
                        [$billing['bill_amount'] ?? 'N/A',  $billing['billing_period'] ?? 'N/A',  $billing['due_date'] ?? 'N/A',  $billing['last_payment_amount'] ?? 'N/A',  $billing['last_payment_date'] ?? 'N/A',  $billing['or_number'] ?? 'N/A'],
                        $replyContent
                    );
                }

                $autoReply = Message::create([
                    'content' => $replyContent,
                    'type'    => 'auto_reply',
                    'user_id' => null,
                ]);

            } else {
                // No keyword matched — reset context and send fallback
                $contact->update(['last_keyword_id' => null]);

                $autoReply = Message::create([
                    'content' => "MORESCO-1: Invalid keyword.\nSend HELP to view available commands.",
                    'type'    => 'auto_reply',
                    'user_id' => null,
                ]);
            }

            // ── 6. Dispatch auto-reply via Yeastar ────────────────────────────
            if ($autoReply) {
                $destination = preg_replace('/[^0-9+]/', '', $contact->phone_number);
                $gsmPort     = env('YEASTAR_PORT_KEYWORD', 2);

                try {
                    $yeastarService = app(\App\Services\YeastarService::class);
                    $sent           = $yeastarService->sendSms($destination, $autoReply->content, $gsmPort);
                    $dispatchStatus = $sent ? 'sent' : 'failed';
                } catch (\Exception $e) {
                    Log::error('Yeastar Keyword Auto-reply dispatch failed: ' . $e->getMessage());
                    $dispatchStatus = 'failed';
                }

                $autoReply->recipients()->create([
                    'contact_id' => $contact->id,
                    'status'     => $dispatchStatus,
                ]);
            }

            return [
                'incoming'        => $incomingMessage->load('recipients.contact'),
                'auto_reply'      => $autoReply ? $autoReply->load('recipients.contact') : null,
                'keyword_matched' => (bool) $keywordMatch,
            ];
        });
    }
}
