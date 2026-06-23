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
        // FULLTEXT is MySQL-only. On Postgres/SQLite the search controller already
        // falls back to LIKE, so skip cleanly instead of throwing/catching.
        if (DB::connection()->getDriverName() !== 'mysql') return;
        try {
            DB::statement('ALTER TABLE medicines ADD FULLTEXT idx_med_search (name, generic_name)');
        } catch (\Throwable $e) {
            // index already exists — skip
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('medicines')) return;
        if (DB::connection()->getDriverName() !== 'mysql') return;
        try {
            DB::statement('ALTER TABLE medicines DROP INDEX idx_med_search');
        } catch (\Throwable $e) {
            // index missing — skip
        }
    }
};
