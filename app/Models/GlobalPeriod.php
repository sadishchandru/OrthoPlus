<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalPeriod extends Model
{
    protected $fillable = [
        'patient_id', 'surgery_id', 'cpt_code', 'global_start',
        'global_end_90days', 'visits_in_period', 'notes',
    ];

    protected $casts = [
        'visits_in_period'  => 'array',
        'global_start'      => 'date',
        'global_end_90days' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function surgery() { return $this->belongsTo(Surgery::class); }
}
