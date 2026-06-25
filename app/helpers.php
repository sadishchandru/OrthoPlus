<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('like_operator')) {
    /**
     * Case-insensitive LIKE operator for the active driver.
     * Postgres LIKE is case-sensitive → use ILIKE; MySQL/SQLite LIKE is already
     * case-insensitive. Keeps search behaviour identical across all drivers.
     */
    function like_operator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}

if (!function_exists('branding')) {
    /**
     * Merged branding config: config/branding.php defaults overridden by the
     * cached `settings` DB rows. Returns ['theme'=>..,'brand'=>..,'print'=>..].
     * Safe before the settings table/migration exists (falls back to config).
     */
    function branding(): array
    {
        $out = [];
        foreach (['theme', 'brand', 'print'] as $group) {
            $db = [];
            try {
                $db = \App\Models\Setting::get($group, []) ?: [];
            } catch (\Throwable $e) {
                // table missing / boot-time — use defaults only
            }
            $out[$group] = array_replace_recursive(config("branding.$group", []), $db);
        }
        return $out;
    }
}
