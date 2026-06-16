<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            
            // Visit Link
            $table->foreignId('visit_id')->nullable(); // Nullable for walk-ins
            
            // Appointment Details
            $table->string('appointment_number', 50);
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->enum('duration_minutes', ['15', '30', '45', '60'])->default('30');
            
            // Service/Resource Management
            $table->foreignId('service_id')->nullable(); // e.g., physiotherapy, orthopedics consultation
            $table->foreignId('resource_id')->nullable(); // Treatment room, therapist
            
            // Therapist Assignment (Commission tracking)
            $table->foreignId('therapist_id')->nullable();
            $table->decimal('commission_percentage', 5, 2)->default(0);
            
            // Recurring Appointments
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_rule')->nullable(); 
            /*
             * Example:
             * {
             *   "frequency": "weekly",
             *   "interval": 1,
             *   "days_of_week": [1, 3, 5], // Mon, Wed, Fri
             *   "until_date": null
             * }
             */
            
            // Status & Reminders
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('reminder_method')->nullable(); // 'sms', 'whatsapp', 'email'
            $table->json('reminder_details')->nullable(); 
            
            // Waitlist Management
            $table->boolean('is_waitlisted')->default(false);
            $table->foreignId('waitlist_position_id')->nullable();
            
            $table->string('notes')->nullable();
            
            $table->timestamps();
        });
        
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Physiotherapy Session'
            $table->string('name_tamil')->nullable();
            $table->string('name_hindi')->nullable();
            
            $table->decimal('price', 10, 2);
            $table->enum('category', ['consultation', 'physiotherapy', 'exercise_class', 'assessment'])->default('physiotherapy');
            
            $table->text('description')->nullable();
            $table->string('duration_minutes')->nullable(); // e.g., '30'
            
            // Resource management
            $table->foreignId('resource_type_id')->nullable(); // Room, equipment, therapist
            
            $table->json('resources_required')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "type": 'room',
             *     "id": 5,
             *     "name": "PT Room A"
             *   }
             * ]
             */
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_tamil')->nullable();
            $table->string('name_hindi')->nullable();
            
            $table->enum('resource_type', ['room', 'equipment', 'therapist'])->default('therapist');
            $table->text('description')->nullable();
            
            // Capacity/Break Management
            $table->json('break_times')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "type": "lunch",
             *     "start_time": "13:00",
             *     "end_time": "14:00",
             *     "daily": true
             *   }
             * ]
             */
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Create waitlist table
        Schema::create('waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            
            $table->string('patient_name'); // For walk-ins without patient record
            $table->string('phone', 15)->nullable();
            $table->date('waitlisted_at');
            $table->enum('status', ['waiting', 'called', 'abandoned'])->default('waiting');
            
            $table->integer('position')->default(1); // Position in queue
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('services');
        Schema::dropIfExists('appointments');
    }
};
