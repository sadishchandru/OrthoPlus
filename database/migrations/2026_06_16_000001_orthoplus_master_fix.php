<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * OrthoPlus Master Fix — additive schema changes.
 * All guarded so it is safe to re-run / partially-applied DBs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Performance indexes -------------------------------------------
        // patients.op_number already has a unique index (200001) — skip.
        // treatments already has index(patient_id, status) — skip.
        // appointments uses scheduled_date/scheduled_time (no start_at column).
        $this->safeIndex('appointments', ['patient_id', 'scheduled_date'], 'idx_pat_sched');

        // --- Therapists: contact fields (schedule + is_active already exist) -
        Schema::table('therapists', function (Blueprint $table) {
            if (!Schema::hasColumn('therapists', 'phone')) $table->string('phone', 30)->nullable()->after('name');
            if (!Schema::hasColumn('therapists', 'email')) $table->string('email')->nullable()->after('phone');
        });

        // --- Medicines: simple pharmacy-counter stock fields ----------------
        // cost_price/sell_price already exist (map purchase_price/selling_price).
        Schema::table('medicines', function (Blueprint $table) {
            if (!Schema::hasColumn('medicines', 'unit'))          $table->string('unit', 20)->nullable()->after('manufacturer');
            if (!Schema::hasColumn('medicines', 'strength'))      $table->string('strength', 50)->nullable()->after('unit');
            if (!Schema::hasColumn('medicines', 'quantity'))      $table->integer('quantity')->default(0)->after('strength');
            if (!Schema::hasColumn('medicines', 'reorder_level')) $table->integer('reorder_level')->default(0)->after('quantity');
            if (!Schema::hasColumn('medicines', 'expiry_date'))   $table->date('expiry_date')->nullable()->after('reorder_level');
            if (!Schema::hasColumn('medicines', 'hsn_code'))      $table->string('hsn_code', 20)->nullable()->after('expiry_date');
        });

        // --- Invoices: clinical vs pharmacy type (series already exists) -----
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'type')) {
                $table->enum('type', ['clinical', 'pharmacy'])->default('clinical')->after('series');
            }
        });

        // --- Prescriptions: estimated total (items/medications json exist) ---
        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'services')) $table->json('services')->nullable()->after('items');
            if (!Schema::hasColumn('prescriptions', 'estimated_total')) {
                $table->decimal('estimated_total', 10, 2)->default(0)->after('services');
            }
        });

        // --- Roles + pivot --------------------------------------------------
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('label')->nullable();
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->unique(['user_id', 'role_id']);
                $table->timestamps();
            });
        }

        // login convenience: username on users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }
        });

        // --- SOAP templates -------------------------------------------------
        if (!Schema::hasTable('soap_templates')) {
            Schema::create('soap_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('subjective')->nullable();
                $table->text('objective')->nullable();
                $table->text('assessment')->nullable();
                $table->text('plan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /** Add a composite index; skip if it already exists. DB-agnostic — no SHOW INDEX. */
    private function safeIndex(string $table, array $cols, string $name): void
    {
        if (!Schema::hasTable($table)) return;
        foreach ($cols as $c) {
            if (!Schema::hasColumn($table, $c)) return;
        }
        try {
            Schema::table($table, fn(Blueprint $t) => $t->index($cols, $name));
        } catch (\Throwable $e) {
            // index already exists — skip
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('soap_templates');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');

        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('username'));
        Schema::table('prescriptions', fn(Blueprint $t) => $t->dropColumn(['services', 'estimated_total']));
        Schema::table('invoices', fn(Blueprint $t) => $t->dropColumn('type'));
        Schema::table('medicines', fn(Blueprint $t) => $t->dropColumn(['unit', 'strength', 'quantity', 'reorder_level', 'expiry_date', 'hsn_code']));
        Schema::table('therapists', fn(Blueprint $t) => $t->dropColumn(['phone', 'email']));
    }
};
