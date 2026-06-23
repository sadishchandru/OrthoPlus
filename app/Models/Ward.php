<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    protected $fillable = [
        'name', 'type', 'floor', 'total_beds', 'is_active',
    ];

    protected $casts = [
        'total_beds' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function beds() { return $this->hasMany(Bed::class); }
}
