<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user(): void
    {
        $admin = User::create([
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $token = $admin->createToken('token')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson('/api/users', [
                             'username' => 'john_doe',
                             'password' => 'secret123',
                             'role' => 'user',
                         ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User created successfully.',
                     'data' => [
                         'username' => 'john_doe',
                         'role' => 'user',
                         'status' => 'active',
                         'created_by' => $admin->id,
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'username' => 'john_doe',
            'created_by' => $admin->id,
        ]);
    }

    public function test_normal_user_cannot_access_user_management(): void
    {
        $user = User::create([
            'username' => 'normal_user',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $token = $user->createToken('token')->plainTextToken;

        $response = $this->withToken($token)
                         ->getJson('/api/users');

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized. Admin access required.',
                 ]);
    }

    public function test_admin_can_disable_user(): void
    {
        $admin = User::create([
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $targetUser = User::create([
            'username' => 'target_user',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $userToken = $targetUser->createToken('target_token')->plainTextToken;
        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        $disableResponse = $this->withToken($adminToken)
                                ->patchJson('/api/users/' . $targetUser->id . '/status', [
                                    'status' => 'disabled',
                                ]);

        $disableResponse->assertStatus(200)
                        ->assertJson([
                            'success' => true,
                            'data' => [
                                'status' => 'disabled',
                            ],
                        ]);

        // Reset auth state in test application
        app('auth')->forgetGuards();
        $this->flushHeaders();

        $meResponse = $this->withToken($userToken)
                           ->getJson('/api/auth/me');

        $meResponse->assertStatus(401);
    }
}
