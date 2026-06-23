<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpFitting extends Model
{
    protected $fillable = [
        'op_order_id', 'patient_id', 'fitting_date', 'adjustments_made',
        'outcome', 'next_fitting_date', 'created_by',
    ];

    protected $casts = [
        'fitting_date'      => 'date',
        'next_fitting_date' => 'date',
    ];

    public function order()   { return $this->belongsTo(OpOrder::class, 'op_order_id'); }
    public function patient() { return $this->belongsTo(Patient::class); }
}
