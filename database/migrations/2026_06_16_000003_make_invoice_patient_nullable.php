<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Walk-in pharmacy sales have no patient — allow invoices.patient_id NULL.
 * Uses Schema change() (portable: MySQL / SQLite / Postgres) instead of raw
 * ALTER TABLE … MODIFY, which SQLite does not support.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
        });
    }
};
