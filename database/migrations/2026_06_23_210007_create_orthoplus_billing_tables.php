<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1G — Charges & Revenue (in-patient billing).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('charge_master')) {
            Schema::create('charge_master', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category')->default('procedure'); // consultation/procedure/room/lab/pharmacy/surgery
                $table->decimal('charge_amount', 10, 2)->default(0);
                $table->decimal('gst_pct', 5, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('category');
            });
        }

        if (!Schema::hasTable('ip_bills')) {
            Schema::create('ip_bills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->date('bill_date');
                $table->json('line_items')->nullable();
                $table->decimal('room_charges', 12, 2)->default(0);
                $table->decimal('pharmacy_charges', 12, 2)->default(0);
                $table->decimal('surgery_charges', 12, 2)->default(0);
                $table->decimal('implant_charges', 12, 2)->default(0);
                $table->decimal('misc_charges', 12, 2)->default(0);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('gst', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->decimal('paid', 12, 2)->default(0);
                $table->decimal('balance', 12, 2)->default(0);
                $table->json('insurance_claim')->nullable();
                $table->string('status')->default('draft'); // draft/final/paid/partially-paid
                $table->timestamps();
                $table->index('admission_id');
                $table->index('patient_id');
            });
        }

        if (!Schema::hasTable('global_periods')) {
            Schema::create('global_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
                $table->string('cpt_code')->nullable();
                $table->date('global_start')->nullable();
                $table->date('global_end_90days')->nullable();
                $table->json('visits_in_period')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('patient_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('global_periods');
        Schema::dropIfExists('ip_bills');
        Schema::dropIfExists('charge_master');
    }
};
