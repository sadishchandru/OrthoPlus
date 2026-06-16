<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    protected $fillable = ['medicine_id', 'type', 'qty', 'reason', 'ref_id', 'created_by'];

    public function medicine() { return $this->belongsTo(Medicine::class); }
}
