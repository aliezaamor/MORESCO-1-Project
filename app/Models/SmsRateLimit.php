<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsRateLimit extends Model
{
    protected $fillable = [
        'contact_id',
        'window_start',
        'message_count',
        'is_warned',
        'is_throttled',
        'is_blocked',
        'last_seen_at',
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_warned'    => 'boolean',
        'is_throttled' => 'boolean',
        'is_blocked'   => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_blocked)   return 'Blocked';
        if ($this->is_throttled) return 'Throttled';
        if ($this->is_warned)    return 'Warning';
        return 'Normal';
    }
}
