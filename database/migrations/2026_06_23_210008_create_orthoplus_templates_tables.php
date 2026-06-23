<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1H — Report Templates & Planning.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clinical_templates')) {
            Schema::create('clinical_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('specialty')->nullable();      // fracture/knee/hip/shoulder/spine/operative
                $table->string('template_type')->nullable();  // examination/soap/operative_note/discharge
                $table->json('fields')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('specialty');
            });
        }

        if (!Schema::hasTable('cast_injection_log')) {
            Schema::create('cast_injection_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->date('date');
                $table->string('procedure_type')->default('cast'); // cast/injection/aspiration
                $table->string('body_part')->nullable();
                $table->string('material_used')->nullable();
                $table->string('injection_drug')->nullable();
                $table->string('injection_dose')->nullable();
                $table->date('next_review_date')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('patient_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_injection_log');
        Schema::dropIfExists('clinical_templates');
    }
};
