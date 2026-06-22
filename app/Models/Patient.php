<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'op_number',
        'op_number_prefix',
        'op_number_counter',
        'name',
        'phone',
        'email',
        'dob',
        'gender',
        'photo',
        'address',
        'documents',
        'photos',
        'intake_data',
        'duplicate_detection_fields',
        'status',
        'is_new_patient',
        'is_active',
        'digital_intake_completed',
        'first_visit_date',
    ];

    protected $casts = [
        'dob'                        => 'date',
        'is_active'                  => 'boolean',
        'is_new_patient'             => 'boolean',
        'digital_intake_completed'   => 'boolean',
        'address'                    => 'array',
        'documents'                  => 'array',
        'photos'                     => 'array',
        'intake_data'                => 'array',
        'duplicate_detection_fields' => 'array',
        'first_visit_date'           => 'date',
    ];

    /**
     * Scope a query to only include active patients.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the next OP number prefix for this month/year.
     */
    public function getNextOpNumberPrefix(): string
    {
        $year = date('Y');
        $month = date('m');
        return sprintf('%s-%s', $this->op_number_prefix ?? 'N', sprintf('%02d', (int)$month));
    }

    /**
     * Auto-generate OP number if it doesn't exist.
     */
    public static function generateOpNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Get last patient with same prefix to continue sequence
        $lastPatient = self::query()
            ->where('op_number_prefix', 'N')
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastPatient) {
            $lastSequence = explode('-', $lastPatient->op_number)[1] ?? 0;
            $nextNumber = intval($lastSequence) + 1;
        } else {
            // For new prefix, start at 1
            $nextNumber = 1;
        }
        
        return sprintf('N-%d', $nextNumber);
    }

    /**
     * Check for duplicate patient.
     */
    public static function checkDuplicate(string $phone, string $name): array
    {
        // Match by phone number first (most reliable)
        $patients = self::where('phone', $phone)->active()->get();
        
        if ($patients->count() > 0) {
            return [
                'is_duplicate' => true,
                'message' => 'Patient found with the same phone number: ' . $name,
                'existing_patients' => $patients,
            ];
        }
        
        // If no phone match, check by name + date of birth (fuzzy match)
        if ($name) {
            $patients = self::where('last_name', $name)
                ->orWhere(function($query) use ($name) {
                    $parts = explode(' ', trim($name));
                    foreach ($parts as $part) {
                        $query->orWhere('first_name', $part);
                    }
                })
                ->active()
                ->get();
            
            if ($patients->count() > 0) {
                // Check if any of them have similar DOB (within 30 days)
                foreach ($patients as $patient) {
                    if (!empty($patient->dob)) {
                        $daysDiff = abs(
                            now()->diffInDays($patient->dob, true) - 
                            now()->diffInDays(now()->addMonths(-date('m')), true)
                        );
                        // This is a placeholder - actual implementation would compare DOBs
                    }
                }
            }
        }
        
        return [
            'is_duplicate' => false,
            'message' => 'No duplicate patient found.',
            'existing_patients' => [],
        ];
    }

    /**
     * Digital intake form data.
     */
    public function digitalIntake()
    {
        return $this->morphOne(IntakeForm::class, 'recordable')->ofType('Patient');
    }

    /**
     * Appointments for this patient.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Visits for this patient (all history).
     */
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Prescriptions for this patient.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Payments for this patient.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all visits including past visits for direct doctor mode.
     */
    public function getAllVisits()
    {
        return $this->hasMany(Visit::class, 'type', 'visit');
    }

    /**
     * Clinical records (the visit history). Count drives new vs revisit.
     */
    public function clinicalRecords()
    {
        return $this->hasMany(ClinicalRecord::class);
    }
}