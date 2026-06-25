<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Link a queue token to its clinical record (edit = update, no duplicate) + pain note. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opd_queue', function (Blueprint $table) {
            if (!Schema::hasColumn('opd_queue', 'clinical_record_id')) {
                $table->unsignedBigInteger('clinical_record_id')->nullable()->after('patient_id');
                $table->index('clinical_record_id');
            }
        });
        Schema::table('clinical_records', function (Blueprint $table) {
            if (!Schema::hasColumn('clinical_records', 'pain_description')) {
                $table->text('pain_description')->nullable()->after('body_map');
            }
        });
    }

    public function down(): void
    {
        Schema::table('opd_queue', fn(Blueprint $t) => $t->dropColumn('clinical_record_id'));
        Schema::table('clinical_records', fn(Blueprint $t) => $t->dropColumn('pain_description'));
    }
};
