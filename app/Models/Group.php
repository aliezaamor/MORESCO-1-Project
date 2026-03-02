<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['source', 'name', 'description'];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_group');
    }
}
