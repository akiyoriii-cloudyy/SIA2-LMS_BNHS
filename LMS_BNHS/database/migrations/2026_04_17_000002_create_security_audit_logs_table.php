<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50); // failed_login, security_breach, suspicious_activity
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('description');
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['severity', 'is_resolved']);
            $table->index(['user_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
    }
};
