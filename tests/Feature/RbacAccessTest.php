<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        Route::middleware(['web', 'auth', 'role:admin'])->get('/_rbac/admin-only', fn () => 'ok');
        Route::middleware(['web', 'auth', 'role:teacher'])->get('/_rbac/teacher-only', fn () => 'ok');
        Route::middleware(['web', 'auth', 'permission:users.manage'])->get('/_rbac/perm-users-manage', fn () => 'ok');
        Route::middleware(['web', 'auth', 'permission:gradebook.edit'])->post('/_rbac/perm-gradebook-edit', fn () => 'ok');
    }

    public function test_guest_is_redirected_from_authenticated_routes(): void
    {
        $this->get('/_rbac/admin-only')->assertRedirect('/login');
    }

    public function test_teacher_cannot_access_admin_only_route(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->sync([Role::query()->where('name', 'teacher')->value('id')]);

        $this->actingAs($teacher)->get('/_rbac/admin-only')->assertForbidden();
    }

    public function test_admin_can_access_admin_only_route(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $this->actingAs($admin)->get('/_rbac/admin-only')->assertOk()->assertSee('ok');
    }

    public function test_permission_middleware_allows_admin_users_manage(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $this->actingAs($admin)->get('/_rbac/perm-users-manage')->assertOk();
    }

    public function test_permission_middleware_blocks_teacher_from_users_manage(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->sync([Role::query()->where('name', 'teacher')->value('id')]);

        $this->actingAs($teacher)->get('/_rbac/perm-users-manage')->assertForbidden();
    }

    public function test_teacher_can_edit_gradebook_permission_route(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->sync([Role::query()->where('name', 'teacher')->value('id')]);

        $this->actingAs($teacher)->post('/_rbac/perm-gradebook-edit')->assertOk();
    }
}

