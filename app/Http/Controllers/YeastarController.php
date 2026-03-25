<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SmsProcessingService;

class YeastarController extends Controller
{
    protected $smsService;

    public function __construct(SmsProcessingService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Handle incoming webhook requests from Yeastar TG Series.
     * Expected payload is raw text:
     * Event: ReceivedSMS
     * Privilege: all,smscommand
     * ID:
     * GsmSpan: 2
     * Sender: +639XXXXXX
     * Recvtime: 2026-02-27 13:11:35
     * Index: 1
     * Total: 1
     * Smsc: +639XXXXXX
     * Content: Message Content
     * --END SMS EVENT--
     */
    public function webhook(Request $request)
    {
        $data = [];

        // Check if Yeastar sent this as a GET request with query parameters
        if ($request->isMethod('get')) {
            $data = $request->all();
            Log::info('Received Yeastar Webhook via GET: ', $data);
            
            // Map lower case parameters to expected case if necessary, depending on how TG400 sends them
            if (isset($data['sender'])) $data['Sender'] = $data['sender'];
            if (isset($data['content'])) $data['Content'] = $data['content'];

        } else {
            // Otherwise, process as a raw text POST payload
            $rawPayload = $request->getContent();
            Log::info('Received Yeastar Webhook via POST: ' . $rawPayload);

            // Parse the raw text payload line by line
            // Forcefully remove the Yeastar trailing tag before exploding
            $cleanPayload = str_replace("--END SMS EVENT--", "", $rawPayload);
            $cleanPayload = str_replace("\r", "", $cleanPayload);
            
            $lines = explode("\n", trim($cleanPayload));
            $contentLines = [];
            $isContentStarted = false;

            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }

                if ($isContentStarted) {
                    // If we already saw "Content:", everything else belongs to content
                    $contentLines[] = $line;
                    continue;
                }

                // Look for "Key: Value"
                if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                    $key = trim($matches[1]);
                    $value = trim($matches[2]);
                    
                    if ($key === 'Content') {
                        $isContentStarted = true;
                        if ($value !== '') {
                            $contentLines[] = $value;
                        }
                    } else {
                        $data[$key] = $value;
                    }
                }
            }
            if (!empty($contentLines)) {
                $data['Content'] = implode("\n", $contentLines);
            }
        }

        // Only process if valid Sender and Content
        if (!empty($data['Sender']) && isset($data['Content'])) {
            $sender = $data['Sender'];
            $content = $data['Content'];

            Log::info("Yeastar processing parsed SMS from {$sender}: {$content}");
            
            $port = $data['GsmSpan'] ?? null;
            $index = $data['Index'] ?? null;

            // Dispatch to central service to register contact and process keywords
            $this->smsService->processIncomingMessage($sender, $content, $port);
            
            // Automatically delete from Yeastar gateway to prevent full SIM
            $index = $data['Index'] ?? null;
            $port = $data['GsmSpan'] ?? null;
            if ($index !== null && $port !== null) {
                try {
                    $yeastar = app(\App\Services\YeastarService::class);
                    $yeastar->deleteSms((int)$port, (int)$index);
                } catch (\Exception $e) {
                    Log::error("Yeastar Webhook auto-deletion failed: " . $e->getMessage());
                }
            }

            return response('OK', 200);
        }

        Log::warning('Yeastar Webhook missing Sender or Content field.', $data);
        return response('Bad Request', 400);
    }
}
