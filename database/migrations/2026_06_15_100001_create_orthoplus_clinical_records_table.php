<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('visit_no')->default(1);
            $table->json('soap_notes')->nullable();   // {subjective, objective, assessment, plan}
            $table->json('body_map')->nullable();      // [{point, x, y, label, severity}]
            $table->decimal('vas_score', 4, 2)->nullable();
            $table->json('rom')->nullable();           // [{joint, movement, degrees, normal}]
            $table->json('ortho_tests')->nullable();   // [{name, result, finding}]
            $table->json('outcome_measures')->nullable(); // {type, score, details}
            $table->string('gait_video')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'visit_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_records');
    }
};
