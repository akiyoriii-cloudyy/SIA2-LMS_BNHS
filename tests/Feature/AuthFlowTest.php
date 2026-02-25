<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

