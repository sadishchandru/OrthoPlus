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
        $exists = collect(DB::select('SHOW INDEX FROM medicines'))->pluck('Key_name')->contains('idx_med_search');
        if (!$exists) {
            DB::statement('ALTER TABLE medicines ADD FULLTEXT idx_med_search (name, generic_name)');
        }
    }

    public function down(): void
    {
        $exists = collect(DB::select('SHOW INDEX FROM medicines'))->pluck('Key_name')->contains('idx_med_search');
        if ($exists) {
            DB::statement('ALTER TABLE medicines DROP INDEX idx_med_search');
        }
    }
};
