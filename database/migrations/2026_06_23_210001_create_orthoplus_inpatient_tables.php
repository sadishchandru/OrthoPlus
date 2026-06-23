<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1A — In-Patient & Bed Management.
 * Additive only. Guarded so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wards')) {
            Schema::create('wards', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('general'); // general/icu/private/semi-private/emergency
                $table->string('floor')->nullable();
                $table->integer('total_beds')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('beds')) {
            Schema::create('beds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ward_id')->constrained('wards')->onDelete('cascade');
                $table->string('bed_number');
                $table->string('bed_type')->default('standard'); // standard/icu/electric
                $table->string('status')->default('available');  // available/occupied/maintenance/reserved
                $table->decimal('daily_charge', 10, 2)->default(0);
                $table->json('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('ward_id');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('admissions')) {
            Schema::create('admissions', function (Blueprint $table) {
                $table->id();
                $table->string('ip_number')->unique();          // IP-2025-001
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->date('admission_date');
                $table->date('discharge_date')->nullable();
                $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
                $table->foreignId('bed_id')->nullable()->constrained('beds')->nullOnDelete();
                $table->unsignedBigInteger('admitting_doctor_id')->nullable();
                $table->text('diagnosis')->nullable();
                $table->string('admission_type')->default('elective'); // elective/emergency
                $table->boolean('surgery_planned')->default(false);
                $table->date('surgery_date')->nullable();
                $table->string('status')->default('admitted');   // admitted/discharged/transferred
                $table->decimal('deposit_amount', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('patient_id');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('bed_transfers')) {
            Schema::create('bed_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
                $table->unsignedBigInteger('from_bed_id')->nullable();
                $table->unsignedBigInteger('to_bed_id')->nullable();
                $table->timestamp('transferred_at')->nullable();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('admission_id');
            });
        }

        if (!Schema::hasTable('discharge_summaries')) {
            Schema::create('discharge_summaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->text('diagnosis_final')->nullable();
                $table->json('procedures_done')->nullable();
                $table->string('discharge_condition')->default('stable'); // stable/critical/absconded/lama/death
                $table->date('follow_up_date')->nullable();
                $table->text('discharge_instructions')->nullable();
                $table->json('discharge_medications')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('admission_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discharge_summaries');
        Schema::dropIfExists('bed_transfers');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
