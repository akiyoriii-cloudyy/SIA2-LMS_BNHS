<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('alert_type', 50); // brute_force, suspicious_ip, account_lockout, unusual_activity
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('title');
            $table->text('description');
            $table->json('trigger_data')->nullable();
            $table->string('target_type', 50)->nullable(); // ip, user, system
            $table->string('target_value')->nullable(); // ip_address, user_id, etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('acknowledgment_notes')->nullable();
            $table->integer('occurrence_count')->default(1);
            $table->timestamp('first_occurrence_at')->useCurrent();
            $table->timestamp('last_occurrence_at')->useCurrent();
            $table->timestamps();

            $table->index(['alert_type', 'is_active']);
            $table->index(['severity', 'is_acknowledged']);
            $table->index(['target_type', 'target_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};
