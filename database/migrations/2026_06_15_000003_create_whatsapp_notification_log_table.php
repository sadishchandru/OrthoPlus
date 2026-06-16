<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_notification_log')) { return; }
        Schema::create('whatsapp_notification_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->onDelete('set null');
            $table->string('patient_name', 100);
            $table->string('phone_number', 20)->index();
            $table->string('appointment_id')->nullable();
            $table->string('message_template', 50); // 'reminder', 'confirmation', 'cancellation'
            $table->text('message_body');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamps();
            
            // Index for SMS/WhatsApp reminders
            $table->index(['phone_number', 'status']);
            $table->index(['appointment_id', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notification_log');
    }
};