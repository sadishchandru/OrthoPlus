<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'name', 'generic_name', 'manufacturer',
        'cost_price', 'sell_price', 'batches',
        'drug_interactions', 'status',
        'unit', 'strength', 'quantity', 'reorder_level', 'expiry_date', 'hsn_code',
    ];

    protected $casts = [
        'batches' => 'array',
        'drug_interactions' => 'array',
        'cost_price' => 'float',
        'sell_price' => 'float',
        'quantity' => 'integer',
        'reorder_level' => 'integer',
        'expiry_date' => 'date',
    ];

    public function stock() { return $this->hasMany(MedicineStock::class); }
    public function inventoryLogs() { return $this->hasMany(InventoryLog::class); }

    public function getTotalStockAttribute(): int
    {
        return $this->stock()->sum('quantity_in_stock');
    }
}
