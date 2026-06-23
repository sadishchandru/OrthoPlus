<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'staff_id', 'name', 'role', 'department', 'phone', 'email',
        'qualification', 'license_number', 'join_date', 'salary_type',
        'salary', 'shift_default', 'is_active', 'photo', 'documents',
    ];

    protected $casts = [
        'join_date' => 'date',
        'salary'    => 'float',
        'is_active' => 'boolean',
        'documents' => 'array',
    ];

    public function shifts()        { return $this->hasMany(StaffShift::class); }
    public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
}
