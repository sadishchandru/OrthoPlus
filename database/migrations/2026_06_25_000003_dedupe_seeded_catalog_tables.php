<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Past deploys re-seeded exercises / treatment_catalog with insertOrIgnore on tables
 * that have no UNIQUE(name) → duplicate rows piled up. Remove dupes, keeping the
 * lowest id per name. Seeders now use updateOrInsert(name) so it won't recur.
 * Driver-agnostic (MIN + groupBy). No unique index added — catalog CRUD may legitimately
 * reuse a name.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['exercises', 'treatment_catalog'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'name')) continue;
            $keep = DB::table($table)->selectRaw('MIN(id) as id')->groupBy('name')->pluck('id')->all();
            if ($keep) {
                DB::table($table)->whereNotIn('id', $keep)->delete();
            }
        }
    }

    public function down(): void
    {
        // No-op: cannot resurrect removed duplicate rows.
    }
};
