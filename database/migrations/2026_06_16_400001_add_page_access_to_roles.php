<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Adds page_access JSON to the existing roles table + seeds per role.
 * page_access drives frontend nav visibility + router guard.
 * 'dashboard','patients','appointments','direct-doctor','inventory','pharmacy',
 * 'invoices','treatments','settings'  ·  root = ['*'] (all).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'page_access')) {
            Schema::table('roles', fn(Blueprint $t) => $t->json('page_access')->nullable()->after('label'));
        }

        $map = [
            'root'         => ['*'],
            'doctor'       => ['dashboard', 'patients', 'appointments', 'direct-doctor', 'treatments'],
            'front_office' => ['dashboard', 'patients', 'appointments'],
            'billing'      => ['dashboard', 'patients', 'invoices'],
            'pharmacy'     => ['pharmacy', 'inventory'],
            'therapist'    => ['dashboard', 'appointments', 'treatments'],
        ];
        foreach ($map as $name => $pa) {
            DB::table('roles')->where('name', $name)->update(['page_access' => json_encode($pa)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'page_access')) {
            Schema::table('roles', fn(Blueprint $t) => $t->dropColumn('page_access'));
        }
    }
};
