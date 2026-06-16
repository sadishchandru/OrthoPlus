<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'type', 'capacity', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
