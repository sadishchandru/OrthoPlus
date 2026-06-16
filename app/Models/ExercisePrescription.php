<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExercisePrescription extends Model
{
    protected $fillable = ['patient_id', 'clinical_record_id', 'exercises', 'frequency', 'notes', 'created_by'];

    protected $casts = ['exercises' => 'array'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function clinicalRecord() { return $this->belongsTo(ClinicalRecord::class); }
}
