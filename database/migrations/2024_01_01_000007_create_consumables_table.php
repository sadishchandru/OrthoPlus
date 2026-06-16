<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consumables')) { return; }
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            
            // Consumable Details
            $table->string('name');
            $table->enum('category', ['bandage', 'tape', 'splint', 'brace', 'other'])->default('other');
            $table->string('sku')->nullable(); // Stock Keeping Unit
            
            // Stock Management
            $table->integer('quantity_available');
            $table->integer('minimum_stock_level')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            
            // Storage Information
            $table->integer('storage_location_id')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Vendor Information (JSON)
            $table->json('vendor_info')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};