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
    /**
     * @param string $phoneNumber  The sender's phone number
     * @param string $content      The full SMS text
     * @param int|string|null $port The GSM port it arrived on
     */
    public function processIncomingMessage(string $phoneNumber, string $content, $port = null)
    {
        return DB::transaction(function () use ($phoneNumber, $content, $port) {

            // ── 1. Find or create sender contact ─────────────────────────────
            $normalizedNumber = strlen($phoneNumber) >= 10
                ? substr($phoneNumber, -10)
                : $phoneNumber;

            $contact = Contact::where('phone_number', 'like', '%' . $normalizedNumber)->first();
            
            // ── 1b. Identify MCO name if new or generic ──────────────────────
            $morescoService = app(MorescoDbService::class);
            if (!$contact || str_starts_with($contact->name, 'Consumer ')) {
                $mcoMember = $morescoService->getMemberByPhoneNumber($phoneNumber);
                if ($mcoMember) {
                    $mcoName = $mcoMember['name'];
                    if (!$contact) {
                        $contact = Contact::create([
                            'phone_number' => $phoneNumber,
                            'name'         => $mcoName,
                        ]);
                    } else {
                        // Update existing generic 'Consumer XXXX' name with the real MCO name
                        $contact->update(['name' => $mcoName]);
                    }
                } elseif (!$contact) {
                    $contact = Contact::create([
                        'phone_number' => $phoneNumber,
                        'name'         => 'Consumer ' . substr($phoneNumber, -4),
                    ]);
                }
            }

            // ── 2. Store the incoming message ─────────────────────────────────
            $keywordPort = config('yeastar.port_keyword', 2);
            $incomingType = ($port !== null && (int)$port === (int)$keywordPort) ? 'incoming_keyword' : 'incoming';

            $incomingMessage = Message::create([
                'content' => $content,
                'type'    => $incomingType,
                'user_id' => null,
            ]);
            $incomingMessage->recipients()->create([
                'contact_id' => $contact->id,
                'status'     => 'sent',
            ]);

            // ── 3. Rate limit check ───────────────────────────────────────────
            $rateLimiter = app(RateLimitService::class);
            $rateResult  = $rateLimiter->check($contact);

            if ($rateResult['status'] === 'block') {
                // Only send the block notice on the very first message that crosses the threshold
                if ($rateResult['is_new_block']) {
                    $blockMsg = Message::create([
                        'content' => 'MORESCO-1: You have sent too many messages in a short time. Your account has been temporarily paused. Please try again after a few minutes.',
                        'type'    => 'auto_reply',
                        'user_id' => null,
                    ]);
                    $blockMsg->recipients()->create(['contact_id' => $contact->id, 'status' => 'sent']);

                    // Dispatch block notice via Yeastar — reply on the same port the message arrived on
                    try {
                        $destination = preg_replace('/[^0-9+]/', '', $contact->phone_number);
                        $gsmPort     = $port ?? config('yeastar.port_keyword', 2);
                        app(\App\Services\YeastarService::class)->sendSms($destination, $blockMsg->content, $gsmPort);
                    } catch (\Exception $e) {
                        Log::error('RateLimit block notice dispatch failed: ' . $e->getMessage());
                    }
                }

                return ['incoming' => $incomingMessage->load('recipients.contact'), 'auto_reply' => null, 'keyword_matched' => false, 'rate_limited' => true];
            }

            if ($rateResult['status'] === 'throttle') {
                // Message saved, but silently drop auto-reply
                return ['incoming' => $incomingMessage->load('recipients.contact'), 'auto_reply' => null, 'keyword_matched' => false, 'rate_limited' => true];
            }

            // ── Keyword processing is TM-port only ───────────────────────────
            if ($port === null || (int)$port !== (int)$keywordPort) {
                return ['incoming' => $incomingMessage->load('recipients.contact'), 'auto_reply' => null, 'keyword_matched' => false, 'rate_limited' => false];
            }

            $normalizedContent = strtolower(trim(preg_replace('/\s+/', ' ', $content)));
            
            // ── 3. Find Longest Matching Keyword ──────────────────────────────
            // To support multi-word keywords (e.g., "LAST PAY 50560"), we need to check
            // if the message starts with any of our active keywords.
            $activeKeywords = Keyword::where('is_active', true)
                ->orderByRaw('LENGTH(keyword) DESC') // Check longest first
                ->get();

            $keywordMatch = null;
            $keywordText = '';
            $accountNumber = null;

            // First check context (sub-keywords)
            if ($contact->last_keyword_id) {
                foreach ($activeKeywords->where('parent_id', $contact->last_keyword_id) as $kw) {
                    $trigger = strtolower($kw->keyword);
                    if ($normalizedContent === $trigger || str_starts_with($normalizedContent, $trigger . ' ')) {
                        $keywordMatch = $kw;
                        $keywordText = $trigger;
                        break;
                    }
                }
            }

            // Fallback to global keywords
            if (!$keywordMatch) {
                foreach ($activeKeywords->whereNull('parent_id') as $kw) {
                    $trigger = strtolower($kw->keyword);
                    if ($normalizedContent === $trigger || str_starts_with($normalizedContent, $trigger . ' ')) {
                        $keywordMatch = $kw;
                        $keywordText = $trigger;
                        break;
                    }
                }
            }

            // Extract the remaining text as the account number
            if ($keywordMatch) {
                $remainder = trim(substr($normalizedContent, strlen($keywordText)));
                $remainderParts = explode(' ', $remainder, 2);
                $accountNumber = isset($remainderParts[0]) && $remainderParts[0] !== '' ? $remainderParts[0] : null;
            } else {
                // If no match at all, fallback to the old split logic just to record it
                $parts         = explode(' ', $normalizedContent, 2);
                $keywordText   = $parts[0];
                $accountNumber = isset($parts[1]) ? trim($parts[1]) : null;
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
                    'outage_info',
                    'outage_report',
                    'general_inquiry'
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
                        $member = $morescoService->getMemberByAccountNumber($accountNumber);

                        if (!$member) {
                            $replyContent = "MORESCO-1: Account number '{$accountNumber}' was not found. Please check and try again.";
                            $actionType   = 'static';
                        }
                    }
                }

                // ── Pre-fetch Billing Data ──────────────────────────────────────
                $billing = null;
                if ($member && in_array($actionType, ['billing_info', 'due_date_info', 'payment_history', 'account_status'])) {
                    $billing = $morescoService->getMemberBillingData($accountNumber);
                }

                // ── Action processing ─────────────────────────────────────────
                $inquiryToLog = null;
                switch ($actionType) {

                    case 'billing_info':
                        $rawBalance = str_replace(['₱', ',', ' '], '', $billing['balance'] ?? '0');
                        $hasBalance = (float)$rawBalance > 0.0;
                        $replyContent = $hasBalance
                            ? ($actionData['has_balance'] ?? $replyContent)
                            : ($actionData['no_balance']  ?? $replyContent);
                        break;

                    case 'due_date_info':
                        $rawBalance = str_replace(['₱', ',', ' '], '', $billing['balance'] ?? '0');
                        $hasDue = (float)$rawBalance > 0.0;
                        $replyContent = $hasDue
                            ? ($actionData['has_due'] ?? $replyContent)
                            : ($actionData['settled']  ?? $replyContent);
                        break;

                    case 'payment_history':
                        $replyContent = $actionData['record_found'] ?? $replyContent;
                        break;

                    case 'account_status':
                        // Fetch the actual billing status string (Active, Disconnected, etc) and default to active if not tracked
                        $accStatus   = strtolower($billing['account_status'] ?? 'active');

                        if (str_contains($accStatus, 'active')) {
                            $replyContent = $actionData['active']           ?? $replyContent;
                        } elseif (str_contains($accStatus, 'disconn')) {
                            $replyContent = $actionData['disconnected']     ?? $replyContent;
                        } else {
                            $replyContent = $actionData['for_disconnection'] ?? $replyContent;
                        }
                        break;


                    case 'outage_info':
                    case 'outage_report':
                    case 'general_inquiry':
                        // 1. Identify context
                        $isReport = ($actionType === 'outage_report');
                        $isGeneral = ($actionType === 'general_inquiry');
                        $isInfo = ($actionType === 'outage_info');
                        
                        $outage = null;
                        if ($isInfo) {
                            $outage = $morescoService->getMemberOutageData($accountNumber, $member['sa_code'] ?? null, $member['barangay'] ?? null, $member['municipality'] ?? null);
                        }

                        if ($isInfo) {
                            if ($outage) {
                                $replyContent = $actionData['has_outage'] ?? $replyContent;
                            } else {
                                $replyContent = $actionData['no_outage'] ?? $replyContent;
                            }
                        } else if (($isReport || $isGeneral) && $member) {
                            $parts = explode(' ', trim(preg_replace('/\s+/', ' ', $content)));
                            $wordCount = count($parts);
                            $kw = strtoupper($keywordText);
                            
                            if ($wordCount > 2) {
                                $inquiryText = $content;

                                $fullName = $member['name'] ?? '';
                                $nameParts = explode(',', $fullName, 2);
                                $lastName  = trim($nameParts[0]);
                                $firstName = isset($nameParts[1]) ? trim($nameParts[1]) : '';

                                $inquiryToLog = [
                                    'first_name' => $firstName,
                                    'last_name'  => $lastName,
                                    'contact_no' => $phoneNumber,
                                    'mode'       => 'SMS',
                                    'inquiry'    => $inquiryText,
                                    'address'    => ($member['barangay'] ?? '') . ', ' . ($member['municipality'] ?? ''),
                                    'type_id'    => $isReport ? 1 : 2,
                                    'status_id'  => 1, // New
                                    'account_no' => $accountNumber,
                                    'member_id'  => $member['id']
                                ];

                                $member['inquiry'] = $inquiryText;

                                $successType = $isReport ? 'report' : 'concern';
                                $replyContent = $actionData['detailed'] ?? "MORESCO-1: Thank you. We have logged your {$successType}: \"{$inquiryText}\". Our team will investigate. Stay safe!";
                            } else {
                                $typeString = $isReport ? "report" : "concern";
                                $defaultPrompt = "MORESCO-1: Please include the details of your {$typeString} in a single message.\nExample: {$kw} {$accountNumber} followed by your details.";
                                $replyContent = $actionData['prompt_details'] ?? $defaultPrompt;

                                $inquiryToLog = null; 
                            }
                        }
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
                        ['{name}',               '{account}',      '{area}',                     '{status}',              '{municipality}',               '{barangay}',               '{inquiry}'],
                        [$member['name'] ?? '',  $accountNumber,   $member['service_area'] ?? '', $member['status'] ?? '', $member['municipality'] ?? '', $member['barangay'] ?? '', $member['inquiry'] ?? ''],
                        $replyContent
                    );
                }

                // Billing/payment placeholders — fetched for relevant action types
                if ($billing) {
                    // Calculate dynamic balance block
                    $rawBalance    = (float)str_replace(['₱', ',', ' '], '', $billing['balance'] ?? '0');
                    $rawBillAmount = (float)str_replace(['₱', ',', ' '], '', $billing['bill_amount'] ?? '0');
                    $dynamicBalanceBlock = "";
                    
                    // ONLY show if the balance is strictly greater than the current bill amount
                    // (meaning they have past arrears) AND balance > 0
                    if ($rawBalance > $rawBillAmount && $rawBalance > 0.0) {
                        $formattedBal = $billing['balance'] ?? '₱0.00';
                        $dynamicBalanceBlock = "Running Balance:\n{$formattedBal}\n";
                    }

                    $replyContent = str_replace(
                        ['{bill_amount}',                   '{billing_period}',                   '{due_date}',                   '{reading_date}',                   '{balance}',                   '{dynamic_balance}',               '{last_payment_amount}',                   '{last_payment_date}',                   '{or_number}',                   '{account_status}'           ],
                        [$billing['bill_amount'] ?? 'N/A',  $billing['billing_period'] ?? 'N/A',  $billing['due_date'] ?? 'N/A',  $billing['reading_date'] ?? 'N/A',  $billing['balance'] ?? 'N/A',  $dynamicBalanceBlock,              $billing['last_payment_amount'] ?? 'N/A',  $billing['last_payment_date'] ?? 'N/A',  $billing['or_number'] ?? 'N/A',  $billing['account_status'] ?? 'N/A'],
                        $replyContent
                    );
                }

                // Outage placeholders
                if ($member && $actionType === 'outage_info') {
                    $outage = $morescoService->getMemberOutageData($accountNumber, $member['sa_code'] ?? null, $member['barangay'] ?? null, $member['municipality'] ?? null);
                    $replyContent = str_replace(
                        ['{work_name}',                   '{work_status}',                   '{date_created}',                   '{power_interruption}',                   '{location}',                   '{remarks}'           ],
                        [$outage['work_name'] ?? 'N/A',   $outage['work_status'] ?? 'N/A',   $outage['date_created'] ?? 'N/A',   $outage['power_interruption'] ?? 'N/A',   $outage['location'] ?? 'N/A',   $outage['remarks'] ?? 'N/A'],
                        $replyContent
                    );
                }

                $autoReply = Message::create([
                    'content' => ($rateResult['status'] === 'warn')
                        ? $replyContent . "\n\n⚠️ Note: You are sending many requests. Please slow down to avoid being temporarily blocked."
                        : $replyContent,
                    'type'    => 'auto_reply',
                    'user_id' => null,
                ]);

                if ($inquiryToLog && $autoReply) {
                    $inquiryToLog['action_taken'] = $autoReply->content;
                    $morescoService->logInquiry($inquiryToLog);
                }

            } else {
                // No keyword matched — reset context and send fallback
                $contact->update(['last_keyword_id' => null]);

                $autoReply = Message::create([
                    'content' => "MORESCO-1: Invalid keyword.\nSend HELP to view available commands.",
                    'type'    => 'auto_reply',
                    'user_id' => null,
                ]);
            }

            // ── 6. Dispatch auto-reply via Yeastar — reply on the same port the message arrived on
            if ($autoReply) {
                $destination = preg_replace('/[^0-9+]/', '', $contact->phone_number);
                $gsmPort     = $port ?? config('yeastar.port_keyword', 2);

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
                'rate_limited'    => false,
            ];
        });
    }
}
