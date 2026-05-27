<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolNotification;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_lists_only_own_in_app_rows(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $other = User::factory()->create(['email' => 'other@example.com']);
        $other->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        SchoolNotification::query()->create([
            'user_id' => $admin->id,
            'student_id' => null,
            'type' => 'test',
            'channel' => 'in_app',
            'title' => 'Yours',
            'message' => 'Hello',
            'meta' => null,
            'sent_at' => now(),
            'read_at' => null,
        ]);

        SchoolNotification::query()->create([
            'user_id' => $other->id,
            'student_id' => null,
            'type' => 'test',
            'channel' => 'in_app',
            'title' => 'Theirs',
            'message' => 'Secret',
            'meta' => null,
            'sent_at' => now(),
            'read_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Yours', false);
        $response->assertDontSee('Secret', false);
    }

    public function test_mark_read_sets_read_at(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $n = SchoolNotification::query()->create([
            'user_id' => $admin->id,
            'student_id' => null,
            'type' => 'test',
            'channel' => 'in_app',
            'title' => 'T',
            'message' => 'M',
            'meta' => null,
            'sent_at' => now(),
            'read_at' => null,
        ]);

        $this->actingAs($admin)->post(route('notifications.read', $n))->assertRedirect();

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_cannot_mark_another_users_notification(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $other = User::factory()->create(['email' => 'other@example.com']);
        $other->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $n = SchoolNotification::query()->create([
            'user_id' => $other->id,
            'student_id' => null,
            'type' => 'test',
            'channel' => 'in_app',
            'title' => 'T',
            'message' => 'M',
            'meta' => null,
            'sent_at' => now(),
            'read_at' => null,
        ]);

        $this->actingAs($admin)->post(route('notifications.read', $n))->assertForbidden();
    }

    public function test_assigning_teacher_sends_admin_and_teacher_notifications(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $teacherUser = User::factory()->create(['email' => 'teacher@example.com']);
        $teacherUser->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $teacher = \App\Models\Teacher::query()->create([
            'user_id' => $teacherUser->id,
            'first_name' => 'Ann',
            'last_name' => 'Teacher',
        ]);

        $subject = \App\Models\Subject::query()->create([
            'code' => 'TST101',
            'title' => 'Test Subject',
            'category' => 'core',
        ]);

        $this->actingAs($admin)->post(route('admin.system.subjects.assign-teacher'), [
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
        ])->assertRedirect();

        $this->assertGreaterThanOrEqual(1, SchoolNotification::query()->where('user_id', $admin->id)->count());
        $this->assertGreaterThanOrEqual(1, SchoolNotification::query()->where('user_id', $teacherUser->id)->count());
    }
}
