<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    protected $fillable = [
        'patient_id', 'clinical_record_id', 'treatment_id', 'therapist_id',
        'status', 'commission_pct', 'completed_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'commission_pct' => 'float',
        'completed_at' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function clinicalRecord() { return $this->belongsTo(ClinicalRecord::class); }
    public function catalog() { return $this->belongsTo(TreatmentCatalog::class, 'treatment_id'); }
    public function therapist() { return $this->belongsTo(Therapist::class); }
}
