<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_teacher_with_normalized_profile(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create();
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'role' => 'adviser',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'suffix' => 'Jr.',
            'email' => 'teacher@example.com',
            'phone' => '09123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'teacher@example.com',
        ]);

        $createdUser = User::query()->where('email', 'teacher@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $createdUser->id,
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'suffix' => 'Jr.',
        ]);

        $this->assertDatabaseHas('teachers', [
            'user_id' => $createdUser->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
    }

    public function test_admin_can_edit_user_details_from_management_page(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create();
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $teacher = User::factory()->create(['email' => 'old.teacher@example.com']);
        $teacher->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $this->actingAs($admin)->put(route('admin.users.update', $teacher->id), [
            'role' => 'admin',
            'first_name' => 'Maria',
            'middle_name' => 'L.',
            'last_name' => 'Cruz',
            'suffix' => null,
            'email' => 'new.teacher@example.com',
            'phone' => '09998887777',
        ])->assertRedirect();

        $teacher->refresh();
        $teacher->load(['roles', 'profile']);

        $this->assertSame('new.teacher@example.com', $teacher->email);
        $this->assertTrue($teacher->hasRole('admin'));
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $teacher->id,
            'first_name' => 'Maria',
            'middle_name' => 'L.',
            'last_name' => 'Cruz',
        ]);
    }
}
