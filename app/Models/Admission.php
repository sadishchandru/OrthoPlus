<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    protected $fillable = [
        'ip_number', 'patient_id', 'admission_date', 'discharge_date',
        'ward_id', 'bed_id', 'admitting_doctor_id', 'diagnosis',
        'admission_type', 'surgery_planned', 'surgery_date', 'status',
        'deposit_amount', 'notes', 'created_by',
    ];

    protected $casts = [
        'admission_date'   => 'date',
        'discharge_date'   => 'date',
        'surgery_date'     => 'date',
        'surgery_planned'  => 'boolean',
        'deposit_amount'   => 'float',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function ward()    { return $this->belongsTo(Ward::class); }
    public function bed()     { return $this->belongsTo(Bed::class); }
    public function transfers() { return $this->hasMany(BedTransfer::class); }
    public function dischargeSummary() { return $this->hasOne(DischargeSummary::class); }
    public function surgeries() { return $this->hasMany(Surgery::class); }
    public function bills()     { return $this->hasMany(IpBill::class); }
}
