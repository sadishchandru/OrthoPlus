<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpOrder extends Model
{
    protected $fillable = [
        'patient_id', 'order_type', 'device_name', 'affected_limb',
        'measurements', 'scan_file_url', 'cad_file_url', 'material',
        'fitting_date', 'delivery_date', 'cost', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'measurements'  => 'array',
        'fitting_date'  => 'date',
        'delivery_date' => 'date',
        'cost'          => 'float',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function fittings() { return $this->hasMany(OpFitting::class); }
}
