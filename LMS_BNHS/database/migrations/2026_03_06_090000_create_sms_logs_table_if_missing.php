<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_logs')) {
            return;
        }

        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guardian_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->date('week_start');
            $table->unsignedTinyInteger('absences_count')->default(0);
            $table->string('phone_number');
            $table->text('message');
            $table->string('notification_key')->unique();
            $table->string('provider')->default('twilio');
            $table->string('provider_message_id')->nullable();
            $table->string('status')->default('queued');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['enrollment_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};

