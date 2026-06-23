<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'staff_id', 'from_date', 'to_date', 'type', 'reason',
        'status', 'approved_by',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    public function staff() { return $this->belongsTo(Staff::class); }
}
