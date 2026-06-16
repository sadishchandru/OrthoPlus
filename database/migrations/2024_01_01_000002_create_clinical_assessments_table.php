<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('patients_visits')->onDelete('cascade');
            
            // Pain Assessment (VAS - Visual Analog Scale 0-10)
            $table->decimal('pain_score_vas', 4, 2); // 0 to 10
            $table->enum('pain_description', ['none', 'mild', 'moderate', 'severe', 'excruciating']);
            
            // Body Map (JSON field with coordinates for clickable body parts)
            $table->json('body_map')->nullable(); 
            /*
             * Example body_map JSON:
             * {
             *   "knee": {"pain_level": 7, "swelling": true, "coordinates": [150, 200]},
             *   "ankle": {"pain_level": 3, "swelling": false}
             * }
             */
            
            // ROM Tracking (Range of Motion)
            $table->json('rom_measurements')->nullable(); 
            /*
             * Example:
             * {
             *   "knee_flexion": {"value": 120, "unit": "degrees"},
             *   "knee_extension": {"value": 10, "unit": "degrees"}
             * }
             */
            
            // Orthopedic Tests (Repeater pattern)
            $table->json('orthopedic_tests')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "test_name": "Lachman",
             *     "result": "positive",
             *     "finding": "Anterior cruciate ligament laxity"
             *   },
             *   {
             *     "test_name": "McMurray",
             *     "result": "negative"
             *   }
             * ]
             */
            
            // Outcome Measures (QuickDASH, WOMAC, KOOS)
            $table->enum('outcome_measure_type', ['none', 'quickdash', 'womac', 'koos'])->default('none');
            $table->decimal('outcome_score', 4, 2)->nullable();
            $table->json('outcome_measure_details')->nullable(); 
            
            // Gait Analysis (Video + Annotation)
            $table->string('gait_analysis_video_path')->nullable(); // S3 or local storage
            $table->json('gait_annotations')->nullable(); // Video timestamps with annotations
            
            // Exercise Prescription
            $table->foreignId('exercise_library_entry_id')->nullable();
            $table->boolean('custom_exercise_prescribed')->default(false);
            $table->json('custom_exercises')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "name": "Knee Extension",
             *     "sets": 3,
             *     "reps": 10,
             *     "frequency": "daily",
             *     "video_url": "https://...",
             *     "image_url": "https://..."
             *   }
             * ]
             */
            
            // Assessment Date & Time
            $table->timestamp('assessment_date')->default(now());
            
            $table->text('clinical_notes')->nullable();
            $table->foreignId('doctor_id')->nullable(); // Assessing doctor
            $table->json('treatment_recommendations')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "type": "medication",
             *     "name": "Paracetamol",
             *     "dosage": "500mg",
             *     "frequency": "TID",
             *     "duration_days": 7
             *   },
             *   {
             *     "type": "therapy",
             *     "name": "Ice Pack",
             *     "duration_minutes": 15,
             *     "frequency": "TID"
             *   }
             * ]
             */
            
            // SOAP Note
            $table->json('soap_note')->nullable(); 
            /*
             * {
             *   "subjective": "...",
             *   "objective": "...",
             *   "assessment": "...",
             *   "plan": "..."
             * }
             */
            
            $table->boolean('consent_obtained')->default(false);
            $table->string('consent_form_type')->nullable(); // 'digital_consent', 'paper_consent'
            
            $table->timestamps();
        });
        
        // Create clinical_assessments_photos table for images attached to assessment
        Schema::create('clinical_assessment_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('clinical_assessments')->onDelete('cascade');
            
            $table->string('photo_path'); // Local or S3 path
            $table->string('mime_type'); // 'image/jpeg', 'image/png'
            $table->string('description')->nullable();
            $table->enum('category', ['pre_treatment', 'post_treatment', 'wound', 'other'])->default('other');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_assessment_photos');
        Schema::dropIfExists('clinical_assessments');
    }
};
