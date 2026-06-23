<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdVisit extends Model
{
    protected $fillable = [
        'patient_id', 'clinical_record_id', 'visit_date', 'doctor_id',
        'chief_complaint', 'vitals', 'referral_from', 'referral_to',
        'follow_up_date', 'visit_type', 'token_number',
    ];

    protected $casts = [
        'vitals'         => 'array',
        'visit_date'     => 'date',
        'follow_up_date' => 'date',
    ];

    public function patient()        { return $this->belongsTo(Patient::class); }
    public function clinicalRecord() { return $this->belongsTo(ClinicalRecord::class); }
}
