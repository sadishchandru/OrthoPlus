<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'module')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $column = $table->enum('module', ['clinic', 'hospital', 'both'])->default('clinic');

            if (Schema::hasColumn('users', 'page_access')) {
                $column->after('page_access');
            } elseif (Schema::hasColumn('users', 'username')) {
                $column->after('username');
            } else {
                $column->after('email');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'module')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('module');
        });
    }
};
