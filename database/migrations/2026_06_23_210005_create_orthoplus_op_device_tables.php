<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1E — Orthotic & Prosthetic (O&P).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('op_orders')) {
            Schema::create('op_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->string('order_type')->default('orthotic'); // orthotic/prosthetic
                $table->string('device_name');
                $table->string('affected_limb')->nullable();
                $table->json('measurements')->nullable();
                $table->string('scan_file_url')->nullable();
                $table->string('cad_file_url')->nullable();
                $table->string('material')->nullable();
                $table->date('fitting_date')->nullable();
                $table->date('delivery_date')->nullable();
                $table->decimal('cost', 10, 2)->default(0);
                $table->string('status')->default('ordered'); // ordered/fabricating/fitting/delivered
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('patient_id');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('op_fittings')) {
            Schema::create('op_fittings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('op_order_id')->constrained('op_orders')->onDelete('cascade');
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->date('fitting_date');
                $table->text('adjustments_made')->nullable();
                $table->text('outcome')->nullable();
                $table->date('next_fitting_date')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('op_order_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('op_fittings');
        Schema::dropIfExists('op_orders');
    }
};
