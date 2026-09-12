<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = User::create([
            'username' => 'admin_test',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin_test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Login successful.',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'token',
                         'token_type',
                         'user' => ['id', 'username', 'role', 'status'],
                     ],
                 ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::create([
            'username' => 'user_test',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'user_test',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid username or password.',
                 ]);
    }

    public function test_disabled_user_cannot_login(): void
    {
        User::create([
            'username' => 'disabled_user',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'disabled',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'disabled_user',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Your account has been disabled. Contact administrator.',
                 ]);
    }

    public function test_authenticated_user_can_get_profile_and_logout(): void
    {
        $user = User::create([
            'username' => 'active_user',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                           ->getJson('/api/auth/me');

        $meResponse->assertStatus(200)
                   ->assertJson([
                       'success' => true,
                       'data' => [
                           'username' => 'active_user',
                           'role' => 'user',
                       ],
                   ]);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                               ->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200)
                       ->assertJson([
                           'success' => true,
                           'message' => 'Logged out successfully.',
                       ]);
    }
}
