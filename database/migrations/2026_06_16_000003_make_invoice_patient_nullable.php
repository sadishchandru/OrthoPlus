<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Walk-in pharmacy sales have no patient — allow invoices.patient_id NULL.
 * FK constraint is preserved (MySQL FKs permit NULL values).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices MODIFY patient_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices MODIFY patient_id BIGINT UNSIGNED NOT NULL');
    }
};
