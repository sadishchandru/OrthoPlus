<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1F — Imaging (PACS / medical images).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('imaging_orders')) {
            Schema::create('imaging_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->unsignedBigInteger('ordered_by')->nullable();
                $table->string('modality')->default('xray'); // xray/mri/ct/ultrasound
                $table->string('body_part')->nullable();
                $table->text('clinical_indication')->nullable();
                $table->timestamp('ordered_at')->nullable();
                $table->string('status')->default('ordered'); // ordered/scheduled/completed/reported
                $table->timestamps();
                $table->index('patient_id');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('imaging_studies')) {
            Schema::create('imaging_studies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('imaging_order_id')->constrained('imaging_orders')->onDelete('cascade');
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->date('study_date')->nullable();
                $table->unsignedBigInteger('technician_id')->nullable();
                $table->json('images')->nullable(); // [{url,type,view}]
                $table->string('report_url')->nullable();
                $table->text('radiologist_notes')->nullable();
                $table->timestamp('reported_at')->nullable();
                $table->timestamps();
                $table->index('imaging_order_id');
                $table->index('patient_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_studies');
        Schema::dropIfExists('imaging_orders');
    }
};
