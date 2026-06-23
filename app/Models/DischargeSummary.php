<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DischargeSummary extends Model
{
    protected $fillable = [
        'admission_id', 'patient_id', 'diagnosis_final', 'procedures_done',
        'discharge_condition', 'follow_up_date', 'discharge_instructions',
        'discharge_medications', 'created_by',
    ];

    protected $casts = [
        'procedures_done'       => 'array',
        'discharge_medications' => 'array',
        'follow_up_date'        => 'date',
    ];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function patient()   { return $this->belongsTo(Patient::class); }
}
