<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['user_id', 'content', 'type'];

    public function recipients()
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
