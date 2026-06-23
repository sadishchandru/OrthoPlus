<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImplantUsage extends Model
{
    protected $table = 'implant_usage';

    protected $fillable = [
        'surgery_id', 'patient_id', 'implant_id', 'quantity_used',
        'unit_cost', 'total_cost', 'used_at',
    ];

    protected $casts = [
        'used_at'       => 'datetime',
        'unit_cost'     => 'float',
        'total_cost'    => 'float',
        'quantity_used' => 'integer',
    ];

    public function surgery() { return $this->belongsTo(Surgery::class); }
    public function implant() { return $this->belongsTo(Implant::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
}
