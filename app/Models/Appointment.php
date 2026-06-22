<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id', 'therapist_id', 'visit_id',
        'appointment_number', 'scheduled_date', 'scheduled_time', 'token_number',
        'duration_minutes', 'service_id', 'resource_id',
        'commission_percentage', 'is_recurring', 'recurrence_rule',
        'status', 'reminder_sent_at', 'is_waitlisted', 'notes',
    ];

    protected $casts = [
        'recurrence_rule' => 'array',
        'is_recurring'    => 'boolean',
        'is_waitlisted'   => 'boolean',
        'scheduled_date'  => 'date',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function therapist() { return $this->belongsTo(Therapist::class); }
}
