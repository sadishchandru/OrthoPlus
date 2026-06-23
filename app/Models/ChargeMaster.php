<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeMaster extends Model
{
    protected $table = 'charge_master';

    protected $fillable = [
        'name', 'category', 'charge_amount', 'gst_pct', 'is_active',
    ];

    protected $casts = [
        'charge_amount' => 'float',
        'gst_pct'       => 'float',
        'is_active'     => 'boolean',
    ];
}
