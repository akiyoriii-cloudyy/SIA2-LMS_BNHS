<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');
            $table->decimal('quiz', 5, 2)->nullable();
            $table->decimal('assignment', 5, 2)->nullable();
            $table->decimal('exam', 5, 2)->nullable();
            $table->decimal('quarter_grade', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'subject_assignment_id', 'quarter'], 'uniq_grade_quarter');
        });

        Schema::create('subject_final_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->decimal('q1', 5, 2)->nullable();
            $table->decimal('q2', 5, 2)->nullable();
            $table->decimal('q3', 5, 2)->nullable();
            $table->decimal('q4', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'subject_assignment_id'], 'uniq_subject_final_grade');
        });

        Schema::create('report_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete()->unique();
            $table->decimal('general_average', 5, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('report_card_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->decimal('q1', 5, 2)->nullable();
            $table->decimal('q2', 5, 2)->nullable();
            $table->decimal('q3', 5, 2)->nullable();
            $table->decimal('q4', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['report_card_id', 'subject_assignment_id'], 'uniq_report_card_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_items');
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('subject_final_grades');
        Schema::dropIfExists('grade_entries');
    }
};

