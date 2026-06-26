<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Past deploys re-seeded exercises / treatment_catalog with insertOrIgnore on tables
 * that have no UNIQUE(name) → duplicate rows piled up. For each name keep the lowest id,
 * REPOINT any child FK rows from the dup ids to the kept id, THEN delete the dups.
 * Without the repoint, deleting a referenced catalog row fails on Postgres with
 * FK violation (23503). Seeders now use updateOrInsert(name) so it won't recur.
 */
return new class extends Migration
{
    public function up(): void
    {
        // catalog table => [child table, child FK column]
        $children = [
            'treatment_catalog' => ['treatments', 'treatment_id'],
            'exercises'         => ['exercise_prescriptions', 'exercise_id'],
        ];

        foreach (['exercises', 'treatment_catalog'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'name')) continue;

            // name => [ids…] (ascending). First id = keep, rest = dups.
            $byName = DB::table($table)->orderBy('id')->get(['id', 'name'])->groupBy('name');

            foreach ($byName as $rows) {
                $ids  = $rows->pluck('id')->all();
                if (count($ids) < 2) continue;          // no dup
                $keep = array_shift($ids);              // lowest id
                $dups = $ids;                           // the rest

                // Repoint child references from dups → keep (if the child table exists).
                [$childTable, $childCol] = $children[$table] ?? [null, null];
                if ($childTable && Schema::hasTable($childTable) && Schema::hasColumn($childTable, $childCol)) {
                    DB::table($childTable)->whereIn($childCol, $dups)->update([$childCol => $keep]);
                }

                DB::table($table)->whereIn('id', $dups)->delete();
            }
        }
    }

    public function down(): void
    {
        // No-op: cannot resurrect removed duplicate rows.
    }
};
