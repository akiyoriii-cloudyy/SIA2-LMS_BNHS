<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('term_number');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['semester_id', 'school_year_id', 'term_number'], 'uniq_term_per_sem_sy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('semesters');
    }
};
