<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            
            // Core Patient Information
            $table->string('name'); // English name
            $table->string('name_tamil')->nullable(); // Tamil name
            $table->string('name_hindi')->nullable(); // Hindi name
            $table->string('phone', 15)->unique();
            $table->string('email')->nullable();
            
            // Address details (JSON field pattern like Ayuplus)
            $table->json('address'); 
            
            // OP Number Auto-generation tracking
            $table->string('op_number_prefix', 30); // e.g., 'N' for new patients, 'N-78' for revisits
            $table->integer('op_number_counter')->default(1);
            $table->string('department_id')->nullable(); // e.g., 'ORTHOPEDICS', 'PHYSIOTHERAPY'
            $table->date('first_visit_date')->nullable();
            
            // Duplicate Detection Fields
            $table->boolean('is_duplicate_check_enabled')->default(true);
            $table->json('duplicate_detection_fields'); // ['name', 'phone'] array
            
            // Photo/Document Uploads (JSON field)
            $table->json('photos')->nullable(); // Patient photos
            $table->json('documents')->nullable(); // Consent forms, medical history
            
            // Digital Intake
            $table->boolean('digital_intake_completed')->default(false);
            $table->json('intake_data')->nullable();
            
            // Status & Flags
            $table->enum('status', ['active', 'inactive', 'transferred'])->default('active');
            $table->boolean('is_new_patient')->default(true); // Direct Doctor Mode flag
            $table->timestamps();
            
            // Indexes for duplicate detection
            $table->index(['name', 'phone']);
            $table->index('phone');
        });
        
        // Create patients_visits table for appointment history
        Schema::create('patients_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            
            // Visit Information
            $table->string('visit_number'); // e.g., 'N-78-1' for first revisit
            $table->string('date_of_visit');
            $table->enum('visit_type', ['initial', 'followup', 'emergency', 'rehab'])->default('followup');
            
            // Direct Doctor Mode bypass info
            $table->boolean('direct_doctor_mode')->default(false);
            $table->foreignId('doctor_id')->nullable(); // Assigned doctor for visit
            
            // Status
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->timestamp('reminder_sent_at')->nullable();
            
            $table->timestamps();
        });
        
        // Create op_numbers table for OP No auto-generation (like Ayuplus)
        Schema::create('op_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('prefix'); // e.g., 'N', 'R' for revisit
            $table->string('department_id')->nullable();
            $table->integer('number')->unique(); // 1, 2, 3...
            
            // Revisit counter (N-78-1, N-78-2...)
            $table->integer('revisit_counter')->default(0);
            $table->date('generated_at');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->unique(['prefix', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_numbers');
        Schema::dropIfExists('patients_visits');
        Schema::dropIfExists('patients');
    }
};
