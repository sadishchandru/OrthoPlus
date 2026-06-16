<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sessions');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('validity_days')->default(90);
            $table->json('treatments')->nullable(); // [treatment_catalog_id, ...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patient_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained('packages');
            $table->unsignedInteger('sessions_used')->default(0);
            $table->date('expires_at')->nullable();
            $table->boolean('paid')->default(false);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('series')->default('INV');
            $table->string('invoice_no')->unique();
            $table->json('items'); // [{description, qty, rate, amount}]
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['paid', 'due', 'pending'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->json('insurance_claim')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('patient_packages');
        Schema::dropIfExists('packages');
    }
};
