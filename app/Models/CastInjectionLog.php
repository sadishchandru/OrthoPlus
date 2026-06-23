<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CastInjectionLog extends Model
{
    protected $table = 'cast_injection_log';

    protected $fillable = [
        'patient_id', 'date', 'procedure_type', 'body_part',
        'material_used', 'injection_drug', 'injection_dose',
        'next_review_date', 'doctor_id', 'notes',
    ];

    protected $casts = [
        'date'             => 'date',
        'next_review_date' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
}
