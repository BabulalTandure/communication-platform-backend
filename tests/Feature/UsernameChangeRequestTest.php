<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UsernameChangeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsernameChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_username_change_request(): void
    {
        $user = User::create([
            'username' => 'john_old',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $token = $user->createToken('token')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson('/api/username-change-requests', [
                             'requested_username' => 'john_new',
                         ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Username change request submitted successfully.',
                     'data' => [
                         'old_username' => 'john_old',
                         'requested_username' => 'john_new',
                         'status' => 'pending',
                     ],
                 ]);

        $this->assertDatabaseHas('username_change_requests', [
            'user_id' => $user->id,
            'old_username' => 'john_old',
            'requested_username' => 'john_new',
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_submit_duplicate_pending_request(): void
    {
        $user = User::create([
            'username' => 'john_old',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        UsernameChangeRequest::create([
            'user_id' => $user->id,
            'old_username' => 'john_old',
            'requested_username' => 'john_new',
            'status' => 'pending',
        ]);

        $token = $user->createToken('token')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson('/api/username-change-requests', [
                             'requested_username' => 'john_another',
                         ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'You already have a pending username change request.',
                 ]);
    }

    public function test_admin_can_approve_username_change_request(): void
    {
        $admin = User::create([
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $user = User::create([
            'username' => 'john_old',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $changeRequest = UsernameChangeRequest::create([
            'user_id' => $user->id,
            'old_username' => 'john_old',
            'requested_username' => 'john_approved',
            'status' => 'pending',
        ]);

        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withToken($adminToken)
                         ->postJson('/api/username-change-requests/' . $changeRequest->id . '/approve');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Username change request approved successfully.',
                     'data' => [
                         'status' => 'approved',
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'john_approved',
        ]);
    }

    public function test_admin_can_reject_username_change_request(): void
    {
        $admin = User::create([
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $user = User::create([
            'username' => 'john_old',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $changeRequest = UsernameChangeRequest::create([
            'user_id' => $user->id,
            'old_username' => 'john_old',
            'requested_username' => 'john_rejected',
            'status' => 'pending',
        ]);

        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withToken($adminToken)
                         ->postJson('/api/username-change-requests/' . $changeRequest->id . '/reject');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Username change request rejected successfully.',
                     'data' => [
                         'status' => 'rejected',
                     ],
                 ]);

        // User's username must remain unchanged
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'john_old',
        ]);
    }
}
