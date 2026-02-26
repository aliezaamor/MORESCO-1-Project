<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Log a user activity to the database.
     *
     * @param string $activity The description of the activity.
     */
    protected function logUserActivity(string $activity): void
    {
        try {
            if (auth()->check()) {
                \App\Models\UserActivity::create([
                    'user_id' => auth()->id(),
                    'activity' => $activity,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail to prevent logging from breaking the application flow
        }
    }
}
