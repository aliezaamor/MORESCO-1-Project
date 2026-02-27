<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'type',
        'is_scheduled',
        'scheduled_at',
        'no_reply',
        'category'
    ];

    public function recipients()
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
