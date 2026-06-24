<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Link prescriptions to an OPD visit (patients_visits). Additive, nullable, guarded. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'visit_id')) {
                $table->unsignedBigInteger('visit_id')->nullable()->after('clinical_record_id');
                $table->index('visit_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('visit_id');
        });
    }
};
