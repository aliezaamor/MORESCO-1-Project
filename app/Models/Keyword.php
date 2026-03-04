<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = ['keyword', 'reply_content', 'is_active', 'parent_id', 'action_type', 'action_data'];

    public function parent()
    {
        return $this->belongsTo(Keyword::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Keyword::class, 'parent_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'action_data' => 'array',
    ];
    //
}
