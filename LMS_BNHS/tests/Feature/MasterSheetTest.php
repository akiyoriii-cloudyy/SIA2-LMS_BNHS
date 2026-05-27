<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeEntry;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_sheet_renders_first_second_and_final_columns_per_subject(): void
    {
        ['adviser' => $adviser, 'schoolYear' => $schoolYear, 'section' => $section] = $this->seedMasterSheetFixture();

        $response = $this->actingAs($adviser)->get(route('master-sheet.index', [
            'school_year_id' => $schoolYear->id,
            'grade_level' => 11,
            'section_id' => $section->id,
            'semester' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Practical Research 2', false);
        $response->assertSee('1st', false);
        $response->assertSee('2nd', false);
        $response->assertSee('Final', false);
        $response->assertSee('Culzon, Daren', false);
        $response->assertSee('84.0', false);
        $response->assertSee('80.0', false);
        $response->assertSee('82.0', false);
    }

    public function test_master_sheet_csv_export_includes_first_second_and_final_values(): void
    {
        ['adviser' => $adviser, 'schoolYear' => $schoolYear, 'section' => $section] = $this->seedMasterSheetFixture();

        $response = $this->actingAs($adviser)->get(route('master-sheet.index', [
            'school_year_id' => $schoolYear->id,
            'grade_level' => 11,
            'section_id' => $section->id,
            'semester' => 1,
            'export' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Practical Research 2 (1st)', $content);
        $this->assertStringContainsString('Practical Research 2 (2nd)', $content);
        $this->assertStringContainsString('Practical Research 2 (Final)', $content);
        $this->assertStringContainsString('Culzon, Daren', $content);
        $this->assertStringContainsString('84.0', $content);
        $this->assertStringContainsString('80.0', $content);
        $this->assertStringContainsString('82.0', $content);
    }

    private function seedMasterSheetFixture(): array
    {
        $this->seed(RbacSeeder::class);

        $adviser = User::factory()->create();
        $adviser->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $schoolYear = SchoolYear::query()->create([
            'name' => '2025-2026',
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'name' => 'HUMSS',
            'grade_level' => 11,
            'track' => 'Academic',
            'strand' => 'HUMSS',
        ]);

        $subject = Subject::query()->create([
            'code' => 'PR2',
            'title' => 'Practical Research 2',
            'category' => 'core',
        ]);

        $assignment = SubjectAssignment::query()->create([
            'teacher_id' => null,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
        ]);

        $student = Student::query()->create([
            'lrn' => '13131300001',
            'first_name' => 'Daren',
            'last_name' => 'Culzon',
            'sex' => 'Male',
        ]);

        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'active',
        ]);

        GradeEntry::query()->create([
            'enrollment_id' => $enrollment->id,
            'subject_assignment_id' => $assignment->id,
            'quarter' => 1,
            'quiz' => 84,
            'performance_task' => 84,
            'assignment' => 84,
            'exam' => 84,
            'quarter_grade' => 84,
        ]);

        GradeEntry::query()->create([
            'enrollment_id' => $enrollment->id,
            'subject_assignment_id' => $assignment->id,
            'quarter' => 2,
            'quiz' => 80,
            'performance_task' => 80,
            'assignment' => 80,
            'exam' => 80,
            'quarter_grade' => 80,
        ]);

        return [
            'adviser' => $adviser,
            'schoolYear' => $schoolYear,
            'section' => $section,
        ];
    }
}
