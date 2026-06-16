<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MedicineStock table
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            
            // Core medicine info
            $table->string('name');
            $table->string('name_tamil')->nullable();
            $table->string('name_hindi')->nullable();
            $table->string('generic_name')->nullable(); // e.g., 'Paracetamol'
            $table->string('manufacturer')->nullable();
            
            // Stock tracking (like Ayuplus Inventory)
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('sell_price', 10, 2)->default(0);
            
            // Batch & Expiry
            $table->json('batches')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "batch_number": "ABC123",
             *     "quantity": 100,
             *     "expiry_date": "2025-06-30",
             *     "location": "Shelf B-3"
             *   }
             * ]
             */
            
            // Drug Interactions & Warnings
            $table->json('drug_interactions')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "interacting_drug": "Warfarin",
             *     "risk_level": "high",
             *     "warning": "Increases bleeding risk"
             *   }
             * ]
             */
            
            // Status
            $table->enum('status', ['active', 'discontinued'])->default('active');
            $table->timestamps();
        });
        
        Schema::create('medicines_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            
            // Stock details
            $table->string('batch_number')->unique();
            $table->integer('quantity_in_stock')->default(0);
            $table->decimal('cost_per_unit', 10, 2)->default(0);
            $table->date('expiry_date');
            
            // Location
            $table->string('storage_location')->nullable(); // e.g., 'Refrigerator A'
            
            // Reorder Alerts
            $table->integer('reorder_quantity')->nullable();
            $table->boolean('is_low_stock')->default(false);
            
            $table->timestamps();
        });
        
        // Consumables table
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('name_tamil')->nullable();
            $table->string('name_hindi')->nullable();
            $table->enum('category', ['bandages', 'needles', 'gloves', 'disposable'])->default('bandages');
            
            // Stock tracking
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->json('stock')->nullable(); 
            /*
             * Example:
             * {
             *   "batch_number": "XYZ789",
             *   "quantity": 50,
             *   "location": "Consumables Shelf 1"
             * }
             */
            
            $table->integer('reorder_quantity')->nullable();
            $table->boolean('is_low_stock')->default(false);
            
            $table->timestamps();
        });
        
        // Inventory transactions (Purchase orders, stock adjustments)
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->nullable();
            $table->foreignId('consumable_id')->nullable();
            
            $table->enum('transaction_type', ['purchase', 'issue', 'return', 'adjustment'])->default('purchase');
            $table->decimal('quantity', 10, 2); // Or quantity and cost for purchases
            
            // PO details
            $table->string('po_number')->nullable();
            $table->foreignId('supplier_id')->nullable();
            
            // Batch/Location info
            $table->string('batch_number')->nullable();
            $table->string('location')->nullable();
            
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
        
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 15)->unique();
            $table->text('address')->nullable();
            
            $table->json('contact_persons')->nullable(); 
            /*
             * Example:
             * [
             *   {
             *     "name": "Ravi Kumar",
             *     "phone": "9876543210",
             *     "role": "sales_manager"
             *   }
             * ]
             */
            
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('consumables');
        Schema::dropIfExists('medicines_stock');
        Schema::dropIfExists('medicines');
    }
};