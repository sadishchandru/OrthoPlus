<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdQueue extends Model
{
    protected $table = 'opd_queue';

    protected $fillable = [
        'patient_id', 'token_number', 'date', 'doctor_id',
        'status', 'priority', 'arrival_time', 'seen_time',
        'chief_complaint', 'vitals',
    ];

    protected $casts = [
        'date'         => 'date',
        'arrival_time' => 'datetime',
        'seen_time'    => 'datetime',
        'token_number' => 'integer',
        'vitals'       => 'array',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
}
