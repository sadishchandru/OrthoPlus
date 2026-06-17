<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FULLTEXT index on medicines(name, generic_name) for fast autocomplete. Guarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('medicines')) return;
        // try/catch instead of SHOW INDEX (not portable across DB drivers).
        try {
            DB::statement('ALTER TABLE medicines ADD FULLTEXT idx_med_search (name, generic_name)');
        } catch (\Throwable $e) {
            // index already exists — skip
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('medicines')) return;
        try {
            DB::statement('ALTER TABLE medicines DROP INDEX idx_med_search');
        } catch (\Throwable $e) {
            // index missing — skip
        }
    }
};
