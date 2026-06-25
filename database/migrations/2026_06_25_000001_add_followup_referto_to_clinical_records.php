<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** OPD consult save writes follow_up_date + refer_to onto clinical_records. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_records', function (Blueprint $table) {
            if (!Schema::hasColumn('clinical_records', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('outcome_measures');
            }
            if (!Schema::hasColumn('clinical_records', 'refer_to')) {
                $table->string('refer_to')->nullable()->after('follow_up_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinical_records', function (Blueprint $table) {
            if (Schema::hasColumn('clinical_records', 'refer_to')) $table->dropColumn('refer_to');
            if (Schema::hasColumn('clinical_records', 'follow_up_date')) $table->dropColumn('follow_up_date');
        });
    }
};
