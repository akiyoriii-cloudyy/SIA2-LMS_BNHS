<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('location', 100)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->boolean('is_active')->default(true);
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 50)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
