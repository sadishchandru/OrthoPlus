<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'visit_no', 'soap_notes', 'body_map', 'pain_description',
        'vas_score', 'rom', 'ortho_tests', 'outcome_measures',
        'gait_video', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'soap_notes' => 'array',
        'body_map' => 'array',
        'rom' => 'array',
        'ortho_tests' => 'array',
        'outcome_measures' => 'array',
        'vas_score' => 'float',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function treatments() { return $this->hasMany(Treatment::class); }
    public function exercisePrescriptions() { return $this->hasMany(ExercisePrescription::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
}
