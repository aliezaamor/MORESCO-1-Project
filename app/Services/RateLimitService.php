<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\SmsRateLimit;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    // ── Thresholds ────────────────────────────────────────────────────────────
    const WINDOW_MINUTES = 5;   // Rolling window size
    const WARN_AT        = 3;   // Warn on this message number
    const THROTTLE_AT    = 5;   // Stop auto-replies from this count
    const BLOCK_AT       = 10;  // Block entirely + send one block notice

    /**
     * Check and update the rate limit for an incoming message.
     *
     * Returns a status array:
     *   [
     *     'status'  => 'ok' | 'warn' | 'throttle' | 'block',
     *     'count'   => int,   // current message count in window
     *     'is_new_block' => bool, // true only on the exact message that crosses block threshold
     *   ]
     */
    public function check(Contact $contact): array
    {
        $now        = now();
        $windowSize = self::WINDOW_MINUTES;

        // Load or create the rate limit record for this contact
        $rl = SmsRateLimit::firstOrNew(['contact_id' => $contact->id]);

        // ── Check if the rolling window has expired ───────────────────────────
        $windowExpired = !$rl->window_start
            || $rl->window_start->diffInMinutes($now) >= $windowSize;

        if ($windowExpired) {
            // Reset the window
            $rl->window_start  = $now;
            $rl->message_count = 1;
            $rl->is_warned     = false;
            $rl->is_throttled  = false;
            $rl->is_blocked    = false;
            $rl->last_seen_at  = $now;
            $rl->save();

            return ['status' => 'ok', 'count' => 1, 'is_new_block' => false];
        }

        // ── Increment count in the active window ─────────────────────────────
        $rl->message_count++;
        $rl->last_seen_at = $now;
        $count = $rl->message_count;

        // ── Apply thresholds ─────────────────────────────────────────────────
        $isNewBlock = false;

        if ($count >= self::BLOCK_AT) {
            $isNewBlock       = !$rl->is_blocked; // true only the first time we cross block threshold
            $rl->is_blocked   = true;
            $rl->is_throttled = true;
            $rl->is_warned    = true;
            $rl->save();

            Log::warning("RateLimit BLOCKED contact #{$contact->id} ({$contact->phone_number}) — {$count} msgs in {$windowSize} min.");
            return ['status' => 'block', 'count' => $count, 'is_new_block' => $isNewBlock];
        }

        if ($count >= self::THROTTLE_AT) {
            $rl->is_throttled = true;
            $rl->is_warned    = true;
            $rl->save();

            Log::info("RateLimit THROTTLED contact #{$contact->id} ({$contact->phone_number}) — {$count} msgs in {$windowSize} min.");
            return ['status' => 'throttle', 'count' => $count, 'is_new_block' => false];
        }

        if ($count >= self::WARN_AT) {
            $rl->is_warned = true;
            $rl->save();

            return ['status' => 'warn', 'count' => $count, 'is_new_block' => false];
        }

        // Normal
        $rl->save();
        return ['status' => 'ok', 'count' => $count, 'is_new_block' => false];
    }

    /**
     * Manually reset a contact's rate limit (staff unblock action).
     */
    public function reset(Contact $contact): void
    {
        SmsRateLimit::where('contact_id', $contact->id)->delete();
        Log::info("RateLimit manually reset for contact #{$contact->id} ({$contact->phone_number}) by staff.");
    }

    /**
     * Get all contacts with rate limit data for the Activity Monitor.
     * Only returns contacts with at least one message in the last hour.
     */
    public function getActivityData(): array
    {
        $cutoff = now()->subHour();

        $records = SmsRateLimit::with('contact')
            ->where('last_seen_at', '>=', $cutoff)
            ->orderByDesc('message_count')
            ->get();

        return $records->map(function ($rl) {
            // If window expired, status is effectively normal (counts are stale)
            $windowExpired = !$rl->window_start
                || $rl->window_start->diffInMinutes(now()) >= self::WINDOW_MINUTES;

            return [
                'id'            => $rl->id,
                'contact_id'    => $rl->contact_id,
                'name'          => $rl->contact->name ?? 'Unknown',
                'phone'         => $rl->contact->phone_number ?? '—',
                'message_count' => $windowExpired ? 0 : $rl->message_count,
                'status'        => $windowExpired ? 'normal' : $this->resolveStatus($rl),
                'last_seen_at'  => $rl->last_seen_at?->diffForHumans() ?? '—',
                'window_start'  => $rl->window_start?->format('H:i:s') ?? '—',
            ];
        })->values()->toArray();
    }

    private function resolveStatus(SmsRateLimit $rl): string
    {
        if ($rl->is_blocked)   return 'blocked';
        if ($rl->is_throttled) return 'throttled';
        if ($rl->is_warned)    return 'warning';
        return 'normal';
    }
}
