<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Therapist extends Model
{
    protected $fillable = ['user_id', 'name', 'phone', 'email', 'specialization', 'commission_pct', 'schedule', 'is_active'];

    protected $casts = ['schedule' => 'array', 'commission_pct' => 'float', 'is_active' => 'boolean'];

    public function treatments() { return $this->hasMany(Treatment::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
}
