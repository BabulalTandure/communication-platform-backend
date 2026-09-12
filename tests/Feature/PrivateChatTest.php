<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_and_reuse_private_chat(): void
    {
        $user1 = User::create([
            'username' => 'alice',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $user2 = User::create([
            'username' => 'bob',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $token1 = $user1->createToken('token1')->plainTextToken;

        // First creation
        $response1 = $this->withToken($token1)
                           ->postJson('/api/chats/private', [
                               'recipient_id' => $user2->id,
                           ]);

        $response1->assertStatus(201)
                  ->assertJson([
                      'success' => true,
                      'message' => 'Private chat created successfully.',
                  ]);

        $chatId = $response1->json('data.id');

        // Second creation call should reuse existing chat
        $response2 = $this->withToken($token1)
                           ->postJson('/api/chats/private', [
                               'recipient_id' => $user2->id,
                           ]);

        $response2->assertStatus(200)
                  ->assertJson([
                      'success' => true,
                      'data' => [
                          'id' => $chatId,
                      ],
                  ]);

        $this->assertEquals(1, Chat::where('type', 'private')->count());
    }

    public function test_unauthorized_user_cannot_access_private_chat(): void
    {
        $user1 = User::create([
            'username' => 'alice',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $user2 = User::create([
            'username' => 'bob',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $eavesdropper = User::create([
            'username' => 'eve',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        // Create chat between alice and bob
        $chat = Chat::create(['type' => 'private']);
        $chat->participants()->attach([$user1->id, $user2->id]);

        $eveToken = $eavesdropper->createToken('eve_token')->plainTextToken;

        // Eve tries to access private chat of alice and bob
        $response = $this->withToken($eveToken)
                         ->getJson('/api/chats/' . $chat->id);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized access to this conversation.',
                 ]);
    }
}
