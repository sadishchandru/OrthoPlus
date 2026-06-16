<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out']);
            $table->integer('qty');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable(); // prescription_id or invoice_id
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['medicine_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
