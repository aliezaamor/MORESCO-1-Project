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
    private $lockHandle;

    public function handle(SmsProcessingService $smsService)
    {
        // ── 1. Prevent overlapping instances ────────────────────────────
        $lockPath = storage_path('framework/yeastar_listener.lock');
        $this->lockHandle = fopen($lockPath, 'c+');
        if (!flock($this->lockHandle, LOCK_EX | LOCK_NB)) {
            $this->error('Another instance of the Yeastar listener is already running. Exiting.');
            Log::warning('Blocked duplicate yeastar:listen instance from starting.');
            fclose($this->lockHandle);
            return Command::FAILURE;
        }

        $host = env('YEASTAR_HOST', '10.209.80.8');
        $port = env('YEASTAR_API_PORT', 5038);
        $username = env('YEASTAR_API_USERNAME', 'apiuser');
        $password = env('YEASTAR_API_PASSWORD', 'apipass');

        while (true) {
            $this->info("\n[" . now()->format('Y-m-d H:i:s') . "] Connecting to Yeastar AMI at {$host}:{$port}...");
            Log::info("Yeastar AMI: Connecting to {$host}:{$port}...");

            $socket = @fsockopen($host, $port, $errno, $errstr, 10);

            if (!$socket) {
                $this->error("Could not connect to Yeastar: {$errstr} ({$errno})");
                Log::error("Yeastar AMI Connection Failed: {$errstr} ({$errno})");
                $this->warn("Retrying in 5 seconds...");
                sleep(5);
                continue;
            }

            stream_set_timeout($socket, 1); // 1-second read timeout to prevent blocking endlessly
            
            $this->info("Connected successfully. Attempting to login as '{$username}'...");
            
            // Send AMI Login action
            $loginAction = "Action: Login\r\n";
            $loginAction .= "Username: {$username}\r\n";
            $loginAction .= "Secret: {$password}\r\n";
            $loginAction .= "\r\n";
            
            fwrite($socket, $loginAction);
            fwrite($socket, "Action: Events\r\nEventMask: on\r\n\r\n");

            $currentEvent = [];
            $isContentReading = false;
            $lastDataTime = time();
            
            $this->info(">> Listening for incoming SMS events. Press Ctrl+C to stop listening. <<");
            
            while (!feof($socket)) {
                $line = fgets($socket);
                
                if ($line === false) {
                    // Check if connection is dead due to long inactivity
                    $meta = stream_get_meta_data($socket);
                    if ($meta['eof']) {
                        $this->warn("Stream EOF reached. Server dropped connection.");
                        break; // Break inner loop to reconnect
                    }

                    // Send a Ping every 30 seconds to keep connection alive
                    if (time() - $lastDataTime > 30) {
                        fwrite($socket, "Action: Ping\r\n\r\n");
                        $lastDataTime = time();
                        Log::debug("Yeastar AMI: Ping sent (keepalive)");
                    }

                    usleep(100000); 
                    continue;
                }
                
                $lastDataTime = time(); // Reset idle timer
                $line = str_replace(["\r", "\n"], '', $line);
                
                // Empty line means end of the current AMI event block
                if ($line === '') {
                    if (!empty($currentEvent) && isset($currentEvent['Event']) && $currentEvent['Event'] === 'ReceivedSMS') {
                        $this->processSmsEvent($currentEvent, $smsService, $socket);
                    }
                    $currentEvent = [];
                    $isContentReading = false;
                    continue;
                }

                // If we are reading Content, everything until the next empty line is part of Content
                if ($isContentReading) {
                    $currentEvent['Content'] .= "\n" . $line;
                    continue; // Skip the preg_match below
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
                        Log::info("Yeastar AMI: Authentication accepted");
                    }
                }
            }
            
            fclose($socket);
            $this->warn("Disconnected from Yeastar. Attempting to reconnect in 5 seconds...");
            Log::warning("Yeastar AMI stream disconnected. Reconnecting in 5s.");
            sleep(5);
        }

        return Command::SUCCESS;
    }

    private function processSmsEvent(array $eventData, SmsProcessingService $smsService, $socket)
    {
        $sender = $eventData['Sender'] ?? null;
        $content = $eventData['Content'] ?? null;
        $index = $eventData['Index'] ?? null;
        $port = $eventData['GsmSpan'] ?? null;

        Log::info("Yeastar AMI: ReceivedSMS event — Sender: {$sender}, Port: {$port}, Index: {$index}");

        if ($sender && $content) {
            // Yeastar AMI sometimes URL encodes the Content field (e.g. Hello+guys%2C)
            $content = urldecode($content);
            
            $content = str_replace(['--END SMS EVENT--', "\r\n", "\n", "\r"], '', $content);
            $content = trim($content);
            
            $this->info("Received SMS from {$sender}: {$content} (Port: {$port}, Index: {$index})");

            try {
                try { Log::info("Yeastar AMI processing parsed SMS from {$sender}: {$content}"); } catch (\Throwable $le) {}

                $smsService->processIncomingMessage($sender, $content, $port);
                $this->info("   -> ✓ Successfully saved SMS to database.");
                Log::info("Yeastar AMI: SMS from {$sender} saved successfully — content: {$content}");

            } catch (\Exception $e) {
                $this->error("   -> Failed to save SMS: " . $e->getMessage());
                try { Log::error("Yeastar AMI processing failed: " . $e->getMessage()); } catch (\Throwable $le) {}
            }
        } else {
            $this->warn("Received SMS event missing Sender or Content.");
        }
    }
}
