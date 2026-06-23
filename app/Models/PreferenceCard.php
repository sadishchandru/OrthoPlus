<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreferenceCard extends Model
{
    protected $fillable = [
        'surgery_name', 'surgeon_id', 'instruments', 'implants',
        'draping_notes', 'special_requirements', 'created_by',
    ];

    protected $casts = [
        'instruments' => 'array',
        'implants'    => 'array',
    ];
}
