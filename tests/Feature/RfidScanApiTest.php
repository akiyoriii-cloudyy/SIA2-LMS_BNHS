<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidScanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_rfid_scan_records_attendance(): void
    {
        $this->seed(RbacSeeder::class);

        $teacher = User::factory()->create();
        $teacher->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $plainToken = 'rfid-test-token';
        ApiToken::query()->create([
            'user_id' => $teacher->id,
            'name' => 'mobile',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        $schoolYear = SchoolYear::query()->create([
            'name' => '2025-2026',
            'is_active' => true,
        ]);

        $section = Section::query()->create([
            'name' => 'HUMSS A',
            'grade_level' => 11,
            'strand' => 'HUMSS',
        ]);

        $student = Student::query()->create([
            'lrn' => '110000001111',
            'rfid_uid' => 'RFID-123-ABC',
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'sex' => 'F',
        ]);

        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
        ])->postJson('/api/mobile/rfid/scan', [
            'rfid_uid' => 'RFID-123-ABC',
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
            'section_id' => $section->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.student.id', $student->id)
            ->assertJsonPath('data.status', 'present');

        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $enrollment->id,
            'status' => 'present',
        ]);
    }
}
