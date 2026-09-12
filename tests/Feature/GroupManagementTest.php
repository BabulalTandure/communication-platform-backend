<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_group_and_adds_members_and_chat(): void
    {
        $admin = User::create([
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $user1 = User::create([
            'username' => 'user_one',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withToken($adminToken)
                         ->postJson('/api/groups', [
                             'name' => 'Developers Club',
                             'user_ids' => [$user1->id],
                         ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Group created successfully.',
                     'data' => [
                         'name' => 'Developers Club',
                     ],
                 ]);

        $groupId = $response->json('data.id');

        $this->assertDatabaseHas('groups', ['id' => $groupId, 'name' => 'Developers Club']);
        $this->assertDatabaseHas('group_members', ['group_id' => $groupId, 'user_id' => $user1->id]);
        $this->assertDatabaseHas('chats', ['type' => 'group', 'group_id' => $groupId]);
    }

    public function test_user_can_view_my_groups(): void
    {
        $admin = User::create([
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $user1 = User::create([
            'username' => 'user_one',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $group = Group::create([
            'name' => 'Project Alpha',
            'created_by' => $admin->id,
        ]);
        $group->members()->attach($user1->id);

        $userToken = $user1->createToken('user_token')->plainTextToken;

        $response = $this->withToken($userToken)
                         ->getJson('/api/groups/my-groups');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         ['name' => 'Project Alpha'],
                     ],
                 ]);
    }

    public function test_admin_can_add_and_remove_group_members(): void
    {
        $admin = User::create([
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $user1 = User::create([
            'username' => 'user_one',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $adminToken = $admin->createToken('admin_token')->plainTextToken;

        $createResponse = $this->withToken($adminToken)
                               ->postJson('/api/groups', [
                                   'name' => 'Design Team',
                               ]);

        $groupId = $createResponse->json('data.id');

        // Add user1 to group
        $addResponse = $this->withToken($adminToken)
                            ->postJson('/api/groups/' . $groupId . '/members', [
                                'user_ids' => [$user1->id],
                            ]);

        $addResponse->assertStatus(200);
        $this->assertDatabaseHas('group_members', ['group_id' => $groupId, 'user_id' => $user1->id]);

        // Remove user1 from group
        $removeResponse = $this->withToken($adminToken)
                               ->deleteJson('/api/groups/' . $groupId . '/members/' . $user1->id);

        $removeResponse->assertStatus(200);
        $this->assertDatabaseMissing('group_members', ['group_id' => $groupId, 'user_id' => $user1->id]);
    }
}
