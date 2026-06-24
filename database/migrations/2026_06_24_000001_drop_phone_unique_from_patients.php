<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop UNIQUE on patients.phone — families share a phone, so phone alone is not
 * a duplicate. Duplicate is enforced in PatientController as name + phone combo.
 * Keeps the non-unique phone index for search.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            try {
                $table->dropUnique('patients_phone_unique');
            } catch (\Throwable $e) {
                // already dropped — ignore
            }
        });
    }

    public function down(): void
    {
        // Not restored — re-adding a unique would fail if shared phones exist.
    }
};
