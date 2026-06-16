<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineStock extends Model
{
    protected $table = 'medicines_stock';

    protected $fillable = [
        'medicine_id', 'batch_number', 'quantity_in_stock',
        'cost_per_unit', 'expiry_date', 'storage_location',
        'reorder_quantity', 'is_low_stock',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_low_stock' => 'boolean',
    ];

    public function medicine() { return $this->belongsTo(Medicine::class); }
}
