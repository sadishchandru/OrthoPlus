<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentCatalog extends Model
{
    protected $table = 'treatment_catalog';

    protected $fillable = ['name', 'category', 'duration_min', 'price', 'description', 'is_active'];

    protected $casts = ['price' => 'float', 'is_active' => 'boolean'];

    public function treatments() { return $this->hasMany(Treatment::class, 'treatment_id'); }
}
