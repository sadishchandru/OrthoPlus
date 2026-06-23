<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpBill extends Model
{
    protected $fillable = [
        'admission_id', 'patient_id', 'bill_date', 'line_items',
        'room_charges', 'pharmacy_charges', 'surgery_charges',
        'implant_charges', 'misc_charges', 'subtotal', 'discount',
        'gst', 'total', 'paid', 'balance', 'insurance_claim', 'status',
    ];

    protected $casts = [
        'line_items'       => 'array',
        'insurance_claim'  => 'array',
        'bill_date'        => 'date',
        'room_charges'     => 'float',
        'pharmacy_charges' => 'float',
        'surgery_charges'  => 'float',
        'implant_charges'  => 'float',
        'misc_charges'     => 'float',
        'subtotal'         => 'float',
        'discount'         => 'float',
        'gst'              => 'float',
        'total'            => 'float',
        'paid'             => 'float',
        'balance'          => 'float',
    ];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function patient()   { return $this->belongsTo(Patient::class); }
}
