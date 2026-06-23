<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BedTransfer extends Model
{
    protected $fillable = [
        'admission_id', 'from_bed_id', 'to_bed_id',
        'transferred_at', 'reason', 'created_by',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function fromBed()   { return $this->belongsTo(Bed::class, 'from_bed_id'); }
    public function toBed()     { return $this->belongsTo(Bed::class, 'to_bed_id'); }
}
