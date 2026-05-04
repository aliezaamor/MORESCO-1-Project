<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearYeastarSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yeastar:clear-sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all accumulated SMS (Inbox and Outbox) from the Yeastar gateway';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = env('YEASTAR_HOST', '10.209.80.8');
        $port = env('YEASTAR_API_PORT', 5038);
        $username = env('YEASTAR_API_USERNAME', 'apiuser');
        $password = env('YEASTAR_API_PASSWORD', 'apipass');

        $this->info("Connecting to Yeastar AMI at {$host}:{$port} to clear SMS...");

        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if (!$socket) {
            $this->error("Connection failed: {$errstr} ({$errno})");
            Log::error("yeastar:clear-sms failed to connect: {$errstr} ({$errno})");
            return Command::FAILURE;
        }

        stream_set_timeout($socket, 2);
        
        // Login
        fwrite($socket, "Action: Login\r\nUsername: {$username}\r\nSecret: {$password}\r\n\r\n");
        usleep(500000); // 500ms for login processing
        
        $portsToClear = config('yeastar.gsm_span_map', [1 => 1, 2 => 2]);
        if (empty($portsToClear)) {
            $portsToClear = [1, 2];
        }

        foreach ($portsToClear as $configPort => $actualPort) {
            // Delete all messages (inbox and outbox) on this port
            // The command "sms delete <port> all" clears all messages.
            fwrite($socket, "Action: smscommand\r\nCommand: sms delete {$actualPort} all\r\n\r\n");
            usleep(200000); // 200ms between commands
            $this->info("Sent delete 'all' command for port {$actualPort}");
        }

        fwrite($socket, "Action: Logoff\r\n\r\n");
        fclose($socket);

        $this->info("Successfully sent clear commands to Yeastar gateway.");
        Log::info("Scheduled Yeastar SMS cleanup executed.");

        return Command::SUCCESS;
    }
}
