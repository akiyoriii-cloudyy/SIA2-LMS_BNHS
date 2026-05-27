<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_invalid_credentials_shows_error(): void
    {
        $response = $this->post(route('login.submit'), [
            'email' => 'nope@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_forgot_password_sends_reset_link_status(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHas('status');
    }

    public function test_reset_password_updates_password(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(auth()->attempt(['email' => $user->email, 'password' => 'newpassword123']));
    }

    public function test_admin_password_update_logs_out_and_redirects_to_login(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'oldpassword123',
        ]);
        $admin->roles()->sync([Role::query()->where('name', 'admin')->value('id')]);

        $response = $this->actingAs($admin)->put(route('admin.settings.password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Password changed successfully. Please sign in again.');
        $this->assertGuest();

        $admin->refresh();
        $this->assertTrue(Hash::check('newpassword123', $admin->password));
    }
}
