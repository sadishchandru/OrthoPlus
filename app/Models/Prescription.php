<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'patient_id', 'clinical_record_id', 'visit_id', 'items', 'medications', 'services',
        'estimated_total', 'notes', 'prescription_date', 'doctor_id', 'type',
        'status', 'is_active', 'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'medications' => 'array',
        'services' => 'array',
        'estimated_total' => 'float',
        'prescription_date' => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function clinicalRecord() { return $this->belongsTo(ClinicalRecord::class); }
}
