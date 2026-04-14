<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Services\YeastarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DispatchScheduledMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:dispatch-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for scheduled SMS messages that are due and dispatches them via Yeastar';

    /**
     * Execute the console command.
     */
    public function handle(YeastarService $yeastar)
    {
        $now = Carbon::now();
        $this->info("Checking for scheduled messages at {$now}...");

        // Find scheduled messages that are due and have 'pending' recipients
        $messages = Message::with(['recipients' => function ($query) {
                $query->where('status', 'pending')->with('contact');
            }])
            ->where('is_scheduled', true)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->whereHas('recipients', function ($query) {
                $query->where('status', 'pending');
            })
            ->get();

        if ($messages->isEmpty()) {
            $this->info("No pending scheduled messages found.");
            return Command::SUCCESS;
        }

        $this->info("Found {$messages->count()} scheduled messages to dispatch.");

        foreach ($messages as $message) {
            $gsmPortToUse = null;

            if ($message->type === 'individual') {
                $gsmPortToUse = config('yeastar.port_individual', 2);
            } elseif ($message->type === 'broadcast') {
                $gsmPortToUse = config('yeastar.port_broadcast', 1);
            }

            $isFirst = true;
            foreach ($message->recipients as $recipient) {
                if (!$recipient->contact) continue;

                // Add 3-second gap for broadcasts after the first message
                if ($message->type === 'broadcast') {
                    if (!$isFirst) {
                        sleep(3);
                    }
                    $isFirst = false;
                }

                $destination = preg_replace('/[^0-9+]/', '', $recipient->contact->phone_number);
                
                $sent = $yeastar->sendSms($destination, $message->content, $gsmPortToUse);

                $recipient->update([
                    'status' => $sent ? 'sent' : 'failed'
                ]);
                
                if ($sent) {
                    $this->info("Sent scheduled message to {$destination}");
                } else {
                    $this->error("Failed to send scheduled message to {$destination}");
                }
            }
        }
        
        $this->info("Finished dispatching scheduled messages.");
        return Command::SUCCESS;
    }
}
