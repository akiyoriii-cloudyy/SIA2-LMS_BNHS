<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table): void {
            if (! Schema::hasColumn('teacher_subjects', 'school_year_id')) {
                $table->foreignId('school_year_id')->nullable()->after('subject_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('teacher_subjects', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('school_year_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('teacher_subjects', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('section_id');
            }
        });

        // Ensure we can represent multiple assignments per subject (by section + school year)
        // while keeping legacy uniqueness (teacher_id + subject_id) compatible.
        Schema::table('teacher_subjects', function (Blueprint $table): void {
            // Create scoped unique index for normalized assignment.
            try {
                $table->unique(['teacher_id', 'subject_id', 'school_year_id', 'section_id'], 'uniq_teacher_subject_scoped');
            } catch (\Throwable $e) {
                // ignore (may already exist)
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table): void {
            try {
                $table->dropUnique('uniq_teacher_subject_scoped');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        Schema::table('teacher_subjects', function (Blueprint $table): void {
            if (Schema::hasColumn('teacher_subjects', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('teacher_subjects', 'section_id')) {
                $table->dropConstrainedForeignId('section_id');
            }

            if (Schema::hasColumn('teacher_subjects', 'school_year_id')) {
                $table->dropConstrainedForeignId('school_year_id');
            }
        });
    }
};

