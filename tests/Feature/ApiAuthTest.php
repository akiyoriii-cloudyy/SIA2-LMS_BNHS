<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_api_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@bnhs.local',
            'password' => Hash::make('password'),
        ]);
        $user->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'staff@bnhs.local',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
        $response->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_api_login_returns_bearer_token_for_staff(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@bnhs.local',
            'password' => Hash::make('password'),
        ]);
        $user->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'staff@bnhs.local',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'token_type',
            'rbac' => ['hierarchy', 'your_roles', 'your_permissions'],
            'user' => ['id', 'name', 'email', 'roles', 'permissions'],
        ]);
        $this->assertNotEmpty($response->json('token'));
        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertContains('lms.portal', $response->json('user.permissions'));
        $this->assertContains('records.manage', $response->json('user.permissions'));
    }

    public function test_api_rbac_profile_returns_hierarchy_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@bnhs.local',
            'password' => Hash::make('password'),
        ]);
        $user->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'staff@bnhs.local',
            'password' => 'password',
        ]);
        $token = $login->json('token');

        $response = $this->getJson('/api/auth/rbac', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'rbac' => ['hierarchy', 'your_roles', 'your_permissions'],
            'user' => ['permissions'],
        ]);
        $this->assertContains('admin', $response->json('rbac.your_roles'));
    }

    public function test_api_login_denies_users_without_portal_permission(): void
    {
        $user = User::factory()->create([
            'email' => 'limited@bnhs.local',
            'password' => Hash::make('password'),
        ]);
        $user->roles()->sync([Role::query()->where('name', 'user')->value('id')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'limited@bnhs.local',
            'password' => 'password',
        ]);

        $response->assertForbidden();
        $response->assertJson(['message' => 'Access denied.']);
    }

    public function test_api_forgot_password_returns_generic_acknowledgement(): void
    {
        User::factory()->create(['email' => 'staff@bnhs.local']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'staff@bnhs.local',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'If that email is registered, a password reset link has been sent.',
        ]);
    }

    public function test_api_forgot_password_hides_unknown_emails(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'unknown@bnhs.local',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'If that email is registered, a password reset link has been sent.',
        ]);
    }
}
