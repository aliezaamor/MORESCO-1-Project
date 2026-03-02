<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['source', 'name', 'phone_number', 'email', 'last_keyword_id'];

    public function lastKeyword()
    {
        return $this->belongsTo(Keyword::class , 'last_keyword_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class , 'contact_group');
    }

    public function messages()
    {
        return $this->hasMany(MessageRecipient::class);
    }
}
