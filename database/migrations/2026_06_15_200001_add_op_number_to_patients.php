<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'op_number')) {
                $table->string('op_number', 30)->nullable()->unique()->after('id');
            }
        });

        // Backfill from op_number_prefix where it looks like a full OP No (e.g. 'N-78')
        DB::statement("
            UPDATE patients
            SET op_number = op_number_prefix
            WHERE op_number IS NULL
              AND op_number_prefix LIKE 'N-%'
        ");
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('op_number');
        });
    }
};
