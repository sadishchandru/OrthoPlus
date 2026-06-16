<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('duration_min')->default(30);
            $table->decimal('price', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('clinical_record_id')->nullable()->constrained('clinical_records')->nullOnDelete();
            $table->foreignId('treatment_id')->constrained('treatment_catalog');
            $table->unsignedBigInteger('therapist_id')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'additional'])->default('assigned');
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('treatment_catalog');
    }
};
