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
