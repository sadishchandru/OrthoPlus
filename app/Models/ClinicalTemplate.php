<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalTemplate extends Model
{
    protected $fillable = [
        'name', 'specialty', 'template_type', 'fields', 'is_active',
    ];

    protected $casts = [
        'fields'    => 'array',
        'is_active' => 'boolean',
    ];
}
