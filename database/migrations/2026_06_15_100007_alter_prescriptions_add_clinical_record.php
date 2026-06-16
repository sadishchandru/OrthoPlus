<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create prescriptions table if it was never created by a prior migration
        if (!Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->unsignedBigInteger('clinical_record_id')->nullable();
                $table->date('prescription_date')->nullable();
                $table->unsignedInteger('doctor_id')->nullable();
                $table->enum('type', ['initial', 'repeat', 'refill'])->default('initial');
                $table->json('medications')->nullable();
                $table->json('items')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
            return;
        }

        // Table exists — add missing columns
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'clinical_record_id')) {
                $table->unsignedBigInteger('clinical_record_id')->nullable()->after('patient_id');
            }
            if (!Schema::hasColumn('prescriptions', 'items')) {
                $table->json('items')->nullable();
            }
            if (!Schema::hasColumn('prescriptions', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('prescriptions')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $cols = ['clinical_record_id', 'items', 'notes'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('prescriptions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
