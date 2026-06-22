<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'token_number')) {
                // Daily sequential queue token (per scheduled_date). Nullable for
                // legacy rows. visit_type derived live from clinical-record count.
                $table->unsignedInteger('token_number')->nullable()->after('scheduled_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'token_number')) {
                $table->dropColumn('token_number');
            }
        });
    }
};
