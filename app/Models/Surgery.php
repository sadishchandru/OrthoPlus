<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Surgery extends Model
{
    protected $fillable = [
        'patient_id', 'admission_id', 'surgery_name', 'surgery_type',
        'scheduled_date', 'scheduled_time', 'duration_mins', 'surgeon_id',
        'anesthetist_id', 'scrub_nurse_id', 'or_room', 'implants_required',
        'pre_op_instructions', 'status', 'post_op_notes', 'complications',
        'created_by',
    ];

    protected $casts = [
        'implants_required' => 'array',
        'scheduled_date'    => 'date',
        'duration_mins'     => 'integer',
    ];

    public function patient()   { return $this->belongsTo(Patient::class); }
    public function admission() { return $this->belongsTo(Admission::class); }
    public function implantUsages() { return $this->hasMany(ImplantUsage::class); }
    public function preOpPlan()     { return $this->hasOne(PreOpPlan::class); }
}
