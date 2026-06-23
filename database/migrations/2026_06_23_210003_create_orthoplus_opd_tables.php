<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1C — OPD (Out-Patient Department).
 * Extends the existing clinical_records flow with a daily queue + visit log.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('opd_queue')) {
            Schema::create('opd_queue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->integer('token_number');
                $table->date('date');
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('status')->default('waiting'); // waiting/in-progress/completed/cancelled
                $table->string('priority')->default('normal'); // normal/urgent/emergency
                $table->timestamp('arrival_time')->nullable();
                $table->timestamp('seen_time')->nullable();
                $table->timestamps();
                $table->index(['date', 'status']);
                $table->index('patient_id');
            });
        }

        if (!Schema::hasTable('opd_visits')) {
            Schema::create('opd_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->foreignId('clinical_record_id')->nullable()->constrained('clinical_records')->nullOnDelete();
                $table->date('visit_date');
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->text('chief_complaint')->nullable();
                $table->json('vitals')->nullable();
                $table->string('referral_from')->nullable();
                $table->string('referral_to')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->string('visit_type')->default('new'); // new/follow-up/emergency
                $table->integer('token_number')->nullable();
                $table->timestamps();
                $table->index('patient_id');
                $table->index('visit_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_visits');
        Schema::dropIfExists('opd_queue');
    }
};
