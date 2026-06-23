<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Implant extends Model
{
    protected $fillable = [
        'name', 'manufacturer', 'ref_number', 'size', 'side', 'quantity',
        'unit_cost', 'selling_price', 'expiry_date', 'batch_number',
        'reorder_level', 'is_active',
    ];

    protected $casts = [
        'expiry_date'   => 'date',
        'unit_cost'     => 'float',
        'selling_price' => 'float',
        'quantity'      => 'integer',
        'reorder_level' => 'integer',
        'is_active'     => 'boolean',
    ];

    public function usages() { return $this->hasMany(ImplantUsage::class); }
}
