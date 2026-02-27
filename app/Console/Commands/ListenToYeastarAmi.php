<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\SmsProcessingService;

class ListenToYeastarAmi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yeastar:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to the Yeastar TG Series Asterisk Manager Interface (AMI) for incoming SMS messages';

    /**
     * Execute the console command.
     */
    public function handle(SmsProcessingService $smsService)
    {
        $host = env('YEASTAR_HOST', '10.209.80.8');
        $port = env('YEASTAR_API_PORT', 5038);
        $username = env('YEASTAR_API_USERNAME', 'apiuser');
        $password = env('YEASTAR_API_PASSWORD', 'apipass');

        $this->info("Connecting to Yeastar AMI at {$host}:{$port}...");

        $socket = @fsockopen($host, $port, $errno, $errstr, 10);

        if (!$socket) {
            $this->error("Could not connect to Yeastar: {$errstr} ({$errno})");
            Log::error("Yeastar AMI Connection Failed: {$errstr} ({$errno})");
            return Command::FAILURE;
        }

        stream_set_timeout($socket, 1); // 1 second read timeout to prevent blocking endlessly
        
        $this->info("Connected successfully. Attempting to login as '{$username}'...");
        
        // Send AMI Login action
        $loginAction = "Action: Login\r\n";
        $loginAction .= "Username: {$username}\r\n";
        $loginAction .= "Secret: {$password}\r\n";
        $loginAction .= "\r\n";
        
        fwrite($socket, $loginAction);
        
        $currentEvent = [];
        $isContentReading = false;
        
        $this->info(">> Listening for incoming SMS events. Press Ctrl+C to stop listening. <<");
        
        while (!feof($socket)) {
            $line = fgets($socket);
            
            if ($line === false) {
                // stream_set_timeout might cause fgets to return false if no data is available
                // Just continue waiting loop
                usleep(100000); 
                continue;
            }
            
            $line = str_replace(["\r", "\n"], '', $line);
            
            // Empty line means end of the current AMI event block
            if ($line === '') {
                if (!empty($currentEvent) && isset($currentEvent['Event']) && $currentEvent['Event'] === 'ReceivedSMS') {
                    $this->processSmsEvent($currentEvent, $smsService);
                }
                $currentEvent = [];
                $isContentReading = false;
                continue;
            }

            // If we are reading Content, everything until the next empty line is part of Content
            if ($isContentReading) {
                $currentEvent['Content'] .= "\n" . $line;
                continue;
            }

            // Parse "Key: Value"
            if (preg_match('/^([a-zA-Z0-9_\-]+):\s*(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
                
                if ($key === 'Content') {
                    $currentEvent[$key] = $value;
                    $isContentReading = true; 
                } else {
                    $currentEvent[$key] = $value;
                }
                
                // Print to console for debug when login is successful
                if ($key === 'Message' && str_contains(strtolower($value), 'authentication accepted')) {
                    $this->info("✓ Successfully authenticated with Yeastar API.");
                }
            }
        }
        
        fclose($socket);
        $this->warn("Disconnected from Yeastar.");
        return Command::SUCCESS;
    }

    private function processSmsEvent(array $eventData, SmsProcessingService $smsService)
    {
        $sender = $eventData['Sender'] ?? null;
        $content = $eventData['Content'] ?? null;
        
        if ($sender && $content) {
            $this->info("Received SMS from {$sender}: {$content}");
            Log::info("Yeastar AMI processing parsed SMS from {$sender}: {$content}");
            
            try {
                $smsService->processIncomingMessage($sender, $content);
                $this->info("   -> ✓ Successfully saved SMS to database.");
            } catch (\Exception $e) {
                $this->error("   -> Failed to save SMS: " . $e->getMessage());
                Log::error("Yeastar AMI processing failed: " . $e->getMessage());
            }
        } else {
            $this->warn("Received SMS event missing Sender or Content.");
        }
    }
}
