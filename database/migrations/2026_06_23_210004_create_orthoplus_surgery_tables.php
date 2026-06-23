<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1D — Surgery & OR Management.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('surgeries')) {
            Schema::create('surgeries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
                $table->string('surgery_name');
                $table->string('surgery_type')->default('elective'); // elective/emergency/revision
                $table->date('scheduled_date')->nullable();
                $table->time('scheduled_time')->nullable();
                $table->integer('duration_mins')->nullable();
                $table->unsignedBigInteger('surgeon_id')->nullable();
                $table->unsignedBigInteger('anesthetist_id')->nullable();
                $table->unsignedBigInteger('scrub_nurse_id')->nullable();
                $table->string('or_room')->nullable();
                $table->json('implants_required')->nullable();
                $table->text('pre_op_instructions')->nullable();
                $table->string('status')->default('scheduled'); // scheduled/in-progress/completed/cancelled
                $table->text('post_op_notes')->nullable();
                $table->text('complications')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('patient_id');
                $table->index('scheduled_date');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('implants')) {
            Schema::create('implants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('manufacturer')->nullable();
                $table->string('ref_number')->nullable();
                $table->string('size')->nullable();
                $table->string('side')->nullable(); // left/right/bilateral
                $table->integer('quantity')->default(0);
                $table->decimal('unit_cost', 10, 2)->default(0);
                $table->decimal('selling_price', 10, 2)->default(0);
                $table->date('expiry_date')->nullable();
                $table->string('batch_number')->nullable();
                $table->integer('reorder_level')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('name');
            });
        }

        if (!Schema::hasTable('implant_usage')) {
            Schema::create('implant_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('implant_id')->constrained('implants')->onDelete('cascade');
                $table->integer('quantity_used')->default(1);
                $table->decimal('unit_cost', 10, 2)->default(0);
                $table->decimal('total_cost', 10, 2)->default(0);
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
                $table->index('surgery_id');
                $table->index('implant_id');
            });
        }

        if (!Schema::hasTable('pre_op_plans')) {
            Schema::create('pre_op_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->string('template_type')->nullable(); // hip/knee/shoulder/spine/fracture
                $table->json('plan_data')->nullable();
                $table->json('xray_refs')->nullable();
                $table->text('simulation_notes')->nullable();
                $table->json('implant_selections')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('surgery_id');
            });
        }

        if (!Schema::hasTable('preference_cards')) {
            Schema::create('preference_cards', function (Blueprint $table) {
                $table->id();
                $table->string('surgery_name');
                $table->unsignedBigInteger('surgeon_id')->nullable();
                $table->json('instruments')->nullable();
                $table->json('implants')->nullable();
                $table->text('draping_notes')->nullable();
                $table->text('special_requirements')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('preference_cards');
        Schema::dropIfExists('pre_op_plans');
        Schema::dropIfExists('implant_usage');
        Schema::dropIfExists('implants');
        Schema::dropIfExists('surgeries');
    }
};
