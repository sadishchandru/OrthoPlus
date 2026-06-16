<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientPackage extends Model
{
    protected $fillable = ['patient_id', 'package_id', 'sessions_used', 'expires_at', 'paid'];

    protected $casts = ['expires_at' => 'date', 'paid' => 'boolean'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function package() { return $this->belongsTo(Package::class); }
}
