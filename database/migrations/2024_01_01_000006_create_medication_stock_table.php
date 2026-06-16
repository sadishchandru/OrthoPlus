<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_stock', function (Blueprint $table) {
            $table->id();
            
            // Medication Details
            $table->string('name');
            $table->enum('type', ['medicine', 'consumable'])->default('medicine');
            $table->string('category')->nullable(); // e.g., 'NSAIDs', 'Analgesics', 'Mobility Aids'
            
            // Stock Management
            $table->integer('quantity_available');
            $table->integer('minimum_stock_level')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->enum('status', ['available', 'expiring_soon', 'expired'])->default('available');
            
            // Storage Information
            $table->integer('storage_location_id')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Vendor Information (JSON)
            $table->json('vendor_info')->nullable();
            /*
             * Example: {
             *   "vendor_name": "PharmaCo Ltd",
             *   "vendor_phone": "+91-1234567890",
             *   "reorder_from": "pharma@company.com"
             * }
             */
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_stock');
    }
};