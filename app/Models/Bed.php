<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    protected $fillable = [
        'ward_id', 'bed_number', 'bed_type', 'status',
        'daily_charge', 'features', 'is_active',
    ];

    protected $casts = [
        'features'      => 'array',
        'daily_charge'  => 'float',
        'is_active'     => 'boolean',
    ];

    public function ward() { return $this->belongsTo(Ward::class); }
}
