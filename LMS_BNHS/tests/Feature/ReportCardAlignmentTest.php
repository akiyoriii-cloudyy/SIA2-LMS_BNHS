<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\SubjectFinalGrade;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCardAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_card_shows_subjects_and_grades_aligned_with_master_sheet_data(): void
    {
        $this->seed(RbacSeeder::class);

        $adviser = User::factory()->create();
        $adviser->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $schoolYear = SchoolYear::query()->updateOrCreate(
            ['name' => '2025-2026'],
            ['is_active' => true]
        );

        $section = Section::query()->updateOrCreate(
            ['name' => 'HUMSS', 'grade_level' => 11],
            ['track' => 'Academic', 'strand' => 'HUMSS']
        );

        $subjectPr2 = Subject::query()->updateOrCreate(
            ['code' => 'PR2'],
            ['title' => 'Practical Research 2', 'category' => 'specialized']
        );

        $subjectCpar = Subject::query()->updateOrCreate(
            ['code' => 'CPAR'],
            ['title' => 'Contemporary Philippine Arts in the Regions', 'category' => 'applied']
        );

        $assignmentPr2 = SubjectAssignment::query()->firstOrCreate(
            [
                'section_id' => $section->id,
                'subject_id' => $subjectPr2->id,
                'school_year_id' => $schoolYear->id,
            ],
            ['teacher_id' => null]
        );

        $assignmentCpar = SubjectAssignment::query()->firstOrCreate(
            [
                'section_id' => $section->id,
                'subject_id' => $subjectCpar->id,
                'school_year_id' => $schoolYear->id,
            ],
            ['teacher_id' => null]
        );

        $student = Student::query()->create([
            'lrn' => '13131385948',
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'sex' => 'Female',
        ]);

        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'active',
        ]);

        SubjectFinalGrade::query()->create([
            'enrollment_id' => $enrollment->id,
            'subject_assignment_id' => $assignmentPr2->id,
            'q1' => 84.0,
            'q2' => 86.0,
            'q3' => 88.0,
            'q4' => 90.0,
            'final_grade' => 87.0,
        ]);

        SubjectFinalGrade::query()->create([
            'enrollment_id' => $enrollment->id,
            'subject_assignment_id' => $assignmentCpar->id,
            'q1' => 90.0,
            'q2' => 92.0,
            'q3' => 89.0,
            'q4' => 91.0,
            'final_grade' => 90.5,
        ]);

        $response = $this->actingAs($adviser)->get(route('report-cards.show', [
            $enrollment->id,
            'page' => 'inside',
        ]));

        $response->assertOk();
        $response->assertSee('Practical Research 2', false);
        $response->assertSee('Contemporary Philippine Arts in the Regions', false);
        $response->assertSee('84.0', false);
        $response->assertSee('86.0', false);
        $response->assertSee('85.0', false);
        $response->assertSee('90.0', false);
        $response->assertSee('92.0', false);
        $response->assertSee('91.0', false);
        $response->assertSee('88.0', false);
        $response->assertSee('90.0', false);
        $response->assertSee('89.0', false);
        $response->assertSee('91.0', false);
    }
}
