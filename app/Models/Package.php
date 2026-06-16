<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'sessions', 'price', 'validity_days', 'treatments', 'is_active'];

    protected $casts = ['treatments' => 'array', 'price' => 'float', 'is_active' => 'boolean'];

    public function patientPackages() { return $this->hasMany(PatientPackage::class); }
}
