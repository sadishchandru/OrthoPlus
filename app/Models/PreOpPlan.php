<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreOpPlan extends Model
{
    protected $fillable = [
        'surgery_id', 'patient_id', 'template_type', 'plan_data',
        'xray_refs', 'simulation_notes', 'implant_selections', 'created_by',
    ];

    protected $casts = [
        'plan_data'          => 'array',
        'xray_refs'          => 'array',
        'implant_selections' => 'array',
    ];

    public function surgery() { return $this->belongsTo(Surgery::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
}
