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
        Route::middleware(['web', 'auth', 'role:adviser'])->get('/_rbac/adviser-only', fn () => 'ok');
        Route::middleware(['web', 'auth', 'permission:users.manage'])->get('/_rbac/perm-users-manage', fn () => 'ok');
        Route::middleware(['web', 'auth', 'permission:gradebook.edit'])->post('/_rbac/perm-gradebook-edit', fn () => 'ok');
    }

    public function test_guest_is_redirected_from_authenticated_routes(): void
    {
        $this->get('/_rbac/admin-only')->assertRedirect('/login');
    }

    public function test_adviser_cannot_access_admin_only_route(): void
    {
        $adviser = User::factory()->create();
        $adviser->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $this->actingAs($adviser)->get('/_rbac/admin-only')->assertForbidden();
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

    public function test_permission_middleware_blocks_adviser_from_users_manage(): void
    {
        $adviser = User::factory()->create();
        $adviser->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $this->actingAs($adviser)->get('/_rbac/perm-users-manage')->assertForbidden();
    }

    public function test_adviser_can_edit_gradebook_permission_route(): void
    {
        $adviser = User::factory()->create();
        $adviser->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $this->actingAs($adviser)->post('/_rbac/perm-gradebook-edit')->assertOk();
    }

    public function test_subject_teacher_can_edit_gradebook_permission_route(): void
    {
        $st = User::factory()->create();
        $st->roles()->sync([Role::query()->where('name', 'subject_teacher')->value('id')]);

        $this->actingAs($st)->post('/_rbac/perm-gradebook-edit')->assertOk();
    }

    public function test_subject_teacher_cannot_access_adviser_only_route(): void
    {
        $st = User::factory()->create();
        $st->roles()->sync([Role::query()->where('name', 'subject_teacher')->value('id')]);

        $this->actingAs($st)->get('/_rbac/adviser-only')->assertForbidden();
    }

    public function test_adviser_can_access_adviser_only_route(): void
    {
        $adviser = User::factory()->create();
        $adviser->roles()->sync([Role::query()->where('name', 'adviser')->value('id')]);

        $this->actingAs($adviser)->get('/_rbac/adviser-only')->assertOk();
    }

    public function test_admin_cannot_access_adviser_instructional_routes(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $this->actingAs($admin)->get(route('master-sheet.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('gradebook.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('students.index'))->assertForbidden();
    }

    public function test_admin_can_access_sms_logs_and_mobile_app(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $this->actingAs($admin)->get(route('sms-logs.index'))->assertOk();
        $this->actingAs($admin)->get(route('mobile.app'))->assertOk();
    }
}
