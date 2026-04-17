<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_id', 64)->unique(); // UUID or unique identifier
            $table->string('transaction_type', 50); // user_creation, grade_entry, profile_update, etc.
            $table->string('status', 20)->default('pending'); // pending, committed, rolled_back, failed
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Who initiated
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete(); // Who performed the action
            $table->json('transaction_data'); // Original data before transaction
            $table->json('rollback_data')->nullable(); // Data needed for rollback
            $table->text('description')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['transaction_type', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['performed_by', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_transactions');
    }
};
