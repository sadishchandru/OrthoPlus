<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Real uploaded patient files (images / pdf / doc) — replaces filename-only documents. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('patient_files')) {
            Schema::create('patient_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->string('module')->nullable();        // opd-registration / opd-consulting / ...
                $table->string('original_name');
                $table->string('path');                      // relative path on 'public' disk
                $table->string('mime')->nullable();
                $table->string('category')->default('other'); // image / pdf / doc / other
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->index('patient_id');
                $table->index(['patient_id', 'module']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_files');
    }
};
