<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital upgrade 1B — Staff Management.
 * Additive only. Guarded so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->id();
                $table->string('staff_id')->unique();      // STF-001
                $table->string('name');
                $table->string('role')->default('nurse');  // doctor/nurse/technician/admin/housekeeping
                $table->string('department')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->string('qualification')->nullable();
                $table->string('license_number')->nullable();
                $table->date('join_date')->nullable();
                $table->string('salary_type')->default('fixed'); // fixed/hourly
                $table->decimal('salary', 12, 2)->default(0);
                $table->string('shift_default')->nullable();
                $table->boolean('is_active')->default(true);
                $table->longText('photo')->nullable();
                $table->json('documents')->nullable();
                $table->timestamps();
                $table->index('role');
            });
        }

        if (!Schema::hasTable('staff_shifts')) {
            Schema::create('staff_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
                $table->date('date');
                $table->string('shift')->default('morning'); // morning/afternoon/night/custom
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
                $table->string('status')->default('scheduled'); // scheduled/attended/absent/leave
                $table->timestamps();
                $table->index(['staff_id', 'date']);
            });
        }

        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
                $table->date('from_date');
                $table->date('to_date');
                $table->string('type')->default('casual'); // sick/casual/annual
                $table->text('reason')->nullable();
                $table->string('status')->default('pending'); // pending/approved/rejected
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();
                $table->index('staff_id');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('staff');
    }
};
