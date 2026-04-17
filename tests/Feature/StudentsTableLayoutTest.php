<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentsTableLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_table_uses_enrollment_form_style_columns(): void
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

        $student = Student::query()->create([
            'lrn' => '13131385948',
            'rfid_uid' => 'RFID-TEST-001',
            'first_name' => 'Ana',
            'middle_name' => 'S.',
            'last_name' => 'Santos',
            'sex' => 'Female',
            'age' => 18,
            'date_of_birth' => '2008-08-12',
            'ethnicity' => 'Catholic',
            'address' => 'Purok 3, Bawing, General Santos City, South Cotabato',
        ]);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'active',
        ]);

        $father = Guardian::query()->create([
            'first_name' => 'Juan',
            'last_name' => 'Santos',
            'phone' => '09170000001',
        ]);
        $mother = Guardian::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'phone' => '09170000002',
        ]);
        $guardian = Guardian::query()->create([
            'first_name' => 'Juliet',
            'last_name' => 'Angeles',
            'phone' => '09170000003',
        ]);

        $student->guardians()->attach($father->id, ['relationship' => 'father', 'is_primary' => true, 'receive_sms' => true]);
        $student->guardians()->attach($mother->id, ['relationship' => 'mother', 'is_primary' => false, 'receive_sms' => true]);
        $student->guardians()->attach($guardian->id, ['relationship' => 'guardian', 'is_primary' => false, 'receive_sms' => true]);

        $response = $this->actingAs($adviser)->get(route('students.index', [
            'school_year_id' => $schoolYear->id,
            'grade_level' => 11,
            'section_id' => $section->id,
            'status' => 'active',
        ]));

        $response->assertOk();
        $response->assertSee("Learner Name (Last Name, First Name, Middle Name)", false);
        $response->assertSee('RFID UID', false);
        $response->assertSee('Birth Date (mm/dd/yyyy)', false);
        $response->assertSee('Religious Affiliation', false);
        $response->assertSee('House # / Street / Sitio / Purok', false);
        $response->assertSee("Father's Name", false);
        $response->assertSee("Mother's Maiden Name", false);
        $response->assertSee('Guardian Name (if not living with parents)', false);
        $response->assertSee("Learner's Learning Modality", false);

        $response->assertSee('Santos, Ana S.', false);
        $response->assertSee('RFID-TEST-001', false);
        $response->assertSee('08/12/2008', false);
        $response->assertSee('Catholic', false);
        $response->assertSee('Purok 3', false);
        $response->assertSee('Bawing', false);
        $response->assertSee('General Santos City', false);
        $response->assertSee('South Cotabato', false);
        $response->assertSee('Santos, Juan', false);
        $response->assertSee('Dela Cruz, Maria', false);
        $response->assertSee('Angeles, Juliet', false);
        $response->assertSee('Face to Face', false);
    }
}

