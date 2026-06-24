<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** OPD consulting — add chief_complaint + vitals to opd_queue. Additive, guarded. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opd_queue', function (Blueprint $table) {
            if (!Schema::hasColumn('opd_queue', 'chief_complaint')) $table->text('chief_complaint')->nullable()->after('priority');
            if (!Schema::hasColumn('opd_queue', 'vitals'))          $table->json('vitals')->nullable()->after('chief_complaint');
        });
    }

    public function down(): void
    {
        Schema::table('opd_queue', function (Blueprint $table) {
            $table->dropColumn(['chief_complaint', 'vitals']);
        });
    }
};
