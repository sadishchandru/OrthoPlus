<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id', // nullable for walk-in or direct doctor entry
        'op_number',      // The OP number from the patient record
        'date_of_visit',
        'time_of_visit',
        'type',          // outpatient, daycare, resident, visit (for history)
        'chief_complaint',
        'history_present_illness',
        'pain_location_json', // JSON body map coordinates/regions
        'pain_score_vas',  // Visual Analog Scale 0-10
        'duration_of_pain_hours',
        'pain_character',  // dull, sharp, burning, stabbing
        'pain_radiation',  // Does pain radiate? to where?
        'aggravating_factors_json',
        'relieving_factors_json',
        'muscle_tension_level', // 0-10 scale
        'tenderness_locations_json',
        'joint_stiffness_json',
        
        // ROM (Range of Motion) tracking
        'flexion_degrees_left', 'extension_degrees_left',
        'flexion_degrees_right', 'extension_degrees_right',
        'rotation_degrees_left', 'rotation_degrees_right',
        'abduction_degrees_left', 'abduction_degrees_right',
        'adduction_degrees_left', 'adduction_degrees_right',
        
        // Orthopedic tests performed
        'orthopedic_tests_json', // JSON: ["Trendelenburg", "Thigh Push Off", ...] with results
        'special_tests_json', // Special orthopedic tests
        
        // Outcomes (QuickDASH, WOMAC, KOOS)
        'quickdash_score',
        'womac_score',
        'koos_score',
        'odometer_score',
        
        // Gait analysis
        'gait_analysis_completed_at',
        'gait_video_url',
        'gait_annotation_json', // JSON: timestamps, events per video segment
        
        // Clinical notes (multi-language)
        'physical_exam_notes',
        'assessment_notes',
        'plan_notes',
        
        // Exercise prescription
        'exercise_library_ref_id', // Link to library ID for assigned exercises
        'custom_exercises_json',   // JSON: custom exercise descriptions
        
        // Treatment status
        'is_first_visit',
        'assigned_treatment_id', // Nullable, links to a Treatment plan
        'treatments_completed_json', // JSON of completed treatments from a plan
        'additional_treatments_json', // JSON of additional treatments needed
        
        // Consent and digital intake
        'consent_form_signed_at',
        'digital_intake_completed_at',
        
        // Direct doctor mode
        'is_direct_doctor_entry',
        
        // Language preference for print pages
        'language_preference', // en, ta, hi
        
        // Status
        'status', // scheduled, completed, no-show, cancelled
        'notes',
    ];

    protected $casts = [
        'date_of_visit' => 'date',
        'is_first_visit' => 'boolean',
        'is_direct_doctor_entry' => 'boolean',
        'pain_score_vas' => 'integer',
        'quickdash_score' => 'integer',
        'womac_score' => 'integer',
        'koos_score' => 'integer',
    ];

    protected $appends = ['op_number_display'];

    /**
     * Accessor for OP number display.
     */
    public function getOpNumberDisplayAttribute(): string
    {
        return $this->op_number ?? '';
    }

    /**
     * Scope to include direct doctor visits only.
     */
    public function scopeDirectDoctor($query)
    {
        return $query->where('is_direct_doctor_entry', true);
    }

    /**
     * Get the patient relationship.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the appointment relationship (if applicable).
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Assign or complete treatment.
     */
    public function assignTreatment($treatment)
    {
        $this->assigned_treatment_id = $treatment->id;
        $this->save();
        
        // Update treatment plan as well
        if ($treatment->plan_id) {
            $plan = TreatmentPlan::find($treatment->plan_id);
            $plan->visits_count = $plan->visits_count + 1;
            $plan->save();
        }
    }

    /**
     * Mark visit as completed.
     */
    public function markCompleted()
    {
        $this->status = 'completed';
        $this->consent_form_signed_at = now()->toISOString(); // Assuming digital consent
        $this->save();
        
        // Create or update treatment history entry
        TreatmentHistory::create([
            'visit_id' => $this->id,
            'visit_type' => 'completed',
            'note_type' => 'clinical_note',
            'text' => json_encode($this->notes ?? []),
        ]);
    }

    /**
     * Create treatment history entries for this visit.
     */
    public function saveTreatmentHistory(): array
    {
        $historyEntries = [];
        
        // Assign completed treatments
        if ($this->assigned_treatment_id) {
            $treatment = Treatment::find($this->assigned_treatment_id);
            if ($treatment && $treatment->plan_id) {
                $plan = TreatmentPlan::find($treatment->plan_id);
                
                // Mark visit as completed for this treatment
                foreach ($plan->treatments as $itemTreatment) {
                    if ($itemTreatment->is_completed_at) continue; // Already completed
                    
                    $visitItemHistory = new VisitItemHistory([
                        'visit_id' => $this->id,
                        'plan_item_treatment_id' => $itemTreatment->id,
                        'status' => 'completed',
                        'is_additional' => false,
                        'notes' => json_encode($treatment->notes ?? []),
                    ]);
                    
                    $visitItemHistory->save();
                }
            }
        }
        
        // Additional treatments (if any)
        foreach ($this->additional_treatments_json ?? [] as $index => $additionalTreatment) {
            $plan = TreatmentPlan::where('id', '=', $additionalTreatment['plan_id'])->first();
            
            if ($plan) {
                foreach ($plan->treatments as $itemTreatment) {
                    if ($itemTreatment->is_completed_at) continue;
                    
                    $visitItemHistory = new VisitItemHistory([
                        'visit_id' => $this->id,
                        'plan_item_treatment_id' => $itemTreatment->id,
                        'status' => 'completed',
                        'is_additional' => true,
                        'notes' => json_encode($additionalTreatment['notes'] ?? []),
                    ]);
                    
                    $visitItemHistory->save();
                }
            }
        }
        
        return $historyEntries;
    }

    /**
     * Generate OP number for this visit if needed.
     */
    public static function generateOpNumber(?Patient $patient = null): string
    {
        // Check if this is a revisit (same patient, different appointment)
        if ($patient && $patient->id) {
            return $patient->op_number;
        }
        
        // New patient - generate OP number
        $year = date('Y');
        $month = date('m');
        
        // Continue sequence from last patient
        $lastPatient = Patient::query()
            ->where('op_number_prefix', 'N')
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastPatient) {
            $lastSequence = explode('-', $lastPatient->op_number)[1] ?? 0;
            $nextNumber = intval($lastSequence) + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('N-%d', $nextNumber);
    }

    /**
     * Get all treatments for this visit (including additional ones).
     */
    public function getAllTreatments()
    {
        if ($this->assigned_treatment_id) {
            // Main treatment plan
            $plan = TreatmentPlan::find($this->assigned_treatment_id);
            return collect($plan->treatments ?? []);
        } else {
            // Additional treatments from JSON
            $treatments = collect();
            
            foreach ($this->additional_treatments_json ?? [] as $index => $additionalTreatment) {
                if ($additionalTreatment['plan_id']) {
                    $plan = TreatmentPlan::find($additionalTreatment['plan_id']);
                    $treatments = $treatments->merge($plan->treatments);
                }
            }
            
            return $treatments;
        }
    }

    /**
     * Get body map coordinates from pain_location_json.
     */
    public function getPainLocations(): array
    {
        if (!$this->pain_location_json) return [];
        
        return json_decode($this->pain_location_json, true);
    }

    /**
     * Get orthopedic tests with results.
     */
    public function getOrthopedicTests(): array
    {
        if (!$this->orthopedic_tests_json) return [];
        
        return json_decode($this->orthopedic_tests_json, true);
    }

    /**
     * Get ROM measurements for left side.
     */
    public function getLeftROM(): array
    {
        return [
            'flexion' => $this->flexion_degrees_left ?? null,
            'extension' => $this->extension_degrees_left ?? null,
            'rotation' => $this->rotation_degrees_left ?? null,
            'abduction' => $this->abduction_degrees_left ?? null,
            'adduction' => $this->adduction_degrees_left ?? null,
        ];
    }

    /**
     * Get ROM measurements for right side.
     */
    public function getRightROM(): array
    {
        return [
            'flexion' => $this->flexion_degrees_right ?? null,
            'extension' => $this->extension_degrees_right ?? null,
            'rotation' => $this->rotation_degrees_right ?? null,
            'abduction' => $this->abduction_degrees_right ?? null,
            'adduction' => $this->adduction_degrees_right ?? null,
        ];
    }

    /**
     * Check if this is a first-time visit for this patient.
     */
    public function isFirstVisitForPatient(): bool
    {
        return $this->is_first_visit;
    }

    /**
     * Check if this visit has consent form signed.
     */
    public function hasConsentForm(): bool
    {
        return !is_null($this->consent_form_signed_at);
    }

    /**
     * Scope to include direct doctor mode entries.
     */
    public function scopeDirectDoctorMode($query)
    {
        // Get all visits including past for direct doctor entry
        return $query;
    }
}