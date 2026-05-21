<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_monthly_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->unsignedSmallInteger('report_year');
            $table->unsignedTinyInteger('report_month');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('school_days_total')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['teacher_id', 'section_id', 'school_year_id', 'report_year', 'report_month'],
                'attendance_monthly_reports_unique'
            );
        });

        Schema::create('attendance_monthly_report_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_monthly_report_id')
                ->constrained('attendance_monthly_reports')
                ->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('student_name');
            $table->string('lrn', 32)->nullable();
            $table->unsignedSmallInteger('school_days')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedSmallInteger('late_days')->default(0);
            $table->unsignedSmallInteger('excused_days')->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['attendance_monthly_report_id', 'enrollment_id'],
                'attendance_monthly_report_lines_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_monthly_report_lines');
        Schema::dropIfExists('attendance_monthly_reports');
    }
};
