<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user page_access override. When set, it wins over the role-derived union
 * (root role still implies full access). Null/empty => fall back to roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'page_access')) {
                $t->json('page_access')->nullable()->after('username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('page_access'));
    }
};
