<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoapTemplate extends Model
{
    protected $fillable = ['name', 'subjective', 'objective', 'assessment', 'plan', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
