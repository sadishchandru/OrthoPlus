<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffShift extends Model
{
    protected $fillable = [
        'staff_id', 'date', 'shift', 'start_time', 'end_time',
        'ward_id', 'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function staff() { return $this->belongsTo(Staff::class); }
    public function ward()  { return $this->belongsTo(Ward::class); }
}
