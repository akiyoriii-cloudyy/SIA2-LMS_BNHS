<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_id', 64); // Reference to business_transactions
            $table->string('operation', 20); // insert, update, delete
            $table->string('table_name', 64);
            $table->string('record_id', 64)->nullable(); // ID of the affected record
            $table->json('old_values')->nullable(); // Before state
            $table->json('new_values')->nullable(); // After state
            $table->text('sql_query')->nullable(); // The actual SQL executed
            $table->float('execution_time_ms')->nullable(); // Performance tracking
            $table->boolean('was_successful')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'operation']);
            $table->index(['table_name', 'record_id']);
            $table->index(['was_successful', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
