<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\RateLimitService;
use Illuminate\Http\Request;

class RateLimitController extends Controller
{
    public function __construct(protected RateLimitService $rateLimiter) {}

    /**
     * Show the Activity Monitor page.
     */
    public function index()
    {
        return view('messages.activity');
    }

    /**
     * Return JSON data for the live Activity Monitor table.
     */
    public function data(Request $request)
    {
        return response()->json($this->rateLimiter->getActivityData($request->query('date')));
    }

    /**
     * Return Yeastar AMI listener status and recent log entries.
     */
    public function listenerStatus()
    {
        // Detect whether the listener process is running via lock file
        $lockPath  = storage_path('framework/yeastar_listener.lock');
        $isRunning = false;
        $pid       = null;

        if (file_exists($lockPath)) {
            $handle    = fopen($lockPath, 'c+');
            $isRunning = !flock($handle, LOCK_EX | LOCK_NB); // can't acquire = someone holds it
            if (!$isRunning) flock($handle, LOCK_UN);        // release immediately if we got it
            fclose($handle);
        }

        // Try to get PID via shell_exec (best-effort; may be unavailable in some environments)
        if (function_exists('shell_exec')) {
            $psOutput = @shell_exec('ps aux 2>/dev/null | grep "[y]eastar:listen"');
            if ($psOutput) {
                $parts = preg_split('/\s+/', trim($psOutput));
                $pid   = $parts[1] ?? null;
            }
        }

        // Read today's log file, fallback to main laravel.log
        $today    = now()->format('Y-m-d');
        $logPaths = [
            storage_path("logs/laravel-{$today}.log"),
            storage_path('logs/laravel.log'),
        ];

        $rawLines = [];
        foreach ($logPaths as $path) {
            if (!file_exists($path)) continue;
            $handle = fopen($path, 'r');
            fseek($handle, max(0, filesize($path) - 8000));
            $chunk  = fread($handle, 8000);
            fclose($handle);
            foreach (explode("\n", $chunk) as $line) {
                if (str_contains($line, 'Yeastar AMI')) {
                    $rawLines[] = trim($line);
                }
            }
            if (!empty($rawLines)) break; // prefer dated log if it has entries
        }

        // Parse log lines: [2026-03-31 10:44:08] local.INFO: Yeastar AMI: ...
        $logs = [];
        foreach (array_slice($rawLines, -30) as $line) {
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+\w+\.(\w+):\s+(.+)$/', $line, $m)) continue;
            $logs[] = [
                'time'    => $m[1],
                'level'   => strtoupper($m[2]),
                'message' => rtrim($m[3]),
            ];
        }

        // Status: derived from lock file (most reliable indicator)
        $lastSeenAt = !empty($logs) ? end($logs)['time'] : null;
        if (!file_exists($lockPath)) {
            $status = 'unknown';
        } else {
            $status = $isRunning ? 'connected' : 'disconnected';
        }

        return response()->json([
            'status'       => $status,
            'pid'          => $pid,
            'last_seen_at' => $lastSeenAt,
            'logs'         => $logs,
        ]);
    }

    /**
     * Staff action: manually reset (unblock) a contact's rate limit.
     */
    public function unblock(Contact $contact)
    {
        $this->rateLimiter->reset($contact);

        return response()->json(['success' => true, 'message' => "Rate limit for {$contact->name} has been reset."]);
    }
}
