<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_send_text_message(): void
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

        $chat = Chat::create(['type' => 'private']);
        $chat->participants()->attach([$user1->id, $user2->id]);

        $token1 = $user1->createToken('token1')->plainTextToken;

        $response = $this->withToken($token1)
                         ->postJson('/api/chats/' . $chat->id . '/messages', [
                             'message_type' => 'text',
                             'message_text' => 'Hello Bob!',
                         ]);

        $response->assertStatus(201)
                  ->assertJson([
                      'success' => true,
                      'message' => 'Message sent successfully.',
                      'data' => [
                          'message_type' => 'text',
                          'message_text' => 'Hello Bob!',
                      ],
                  ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'sender_id' => $user1->id,
            'message_text' => 'Hello Bob!',
        ]);
    }

    public function test_participant_can_send_image_and_voice_note(): void
    {
        Storage::fake('public');

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

        $chat = Chat::create(['type' => 'private']);
        $chat->participants()->attach([$user1->id, $user2->id]);

        $token1 = $user1->createToken('token1')->plainTextToken;

        // 1. Send Image
        $fakeImage = UploadedFile::fake()->image('photo.jpg', 600, 600);
        $imageResponse = $this->withToken($token1)
                              ->postJson('/api/chats/' . $chat->id . '/messages', [
                                  'message_type' => 'image',
                                  'file' => $fakeImage,
                              ]);

        $imageResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'data' => [
                               'message_type' => 'image',
                           ],
                       ]);

        $imagePath = $imageResponse->json('data.file_path');
        Storage::disk('public')->assertExists($imagePath);

        // 2. Send Voice Note (MP3 and M4A)
        $fakeVoice = UploadedFile::fake()->create('note.mp3', 200, 'audio/mpeg');
        $voiceResponse = $this->withToken($token1)
                              ->postJson('/api/chats/' . $chat->id . '/messages', [
                                  'message_type' => 'voice',
                                  'file' => $fakeVoice,
                              ]);

        $voiceResponse->assertStatus(201)
                       ->assertJson([
                           'success' => true,
                           'data' => [
                               'message_type' => 'voice',
                           ],
                       ]);

        $voicePath = $voiceResponse->json('data.file_path');
        Storage::disk('public')->assertExists($voicePath);

        // 3. Send Voice Note (.m4a AAC audio in MP4 container)
        $fakeM4aVoice = UploadedFile::fake()->create('voice_12345.m4a', 200, 'audio/x-m4a');
        $m4aResponse = $this->withToken($token1)
                             ->postJson('/api/chats/' . $chat->id . '/messages', [
                                 'message_type' => 'voice',
                                 'file' => $fakeM4aVoice,
                             ]);

        $m4aResponse->assertStatus(201)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'message_type' => 'voice',
                        ],
                    ]);

        $m4aPath = $m4aResponse->json('data.file_path');
        Storage::disk('public')->assertExists($m4aPath);
    }

    public function test_non_participant_cannot_send_or_read_messages(): void
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

        $intruder = User::create([
            'username' => 'intruder',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active',
        ]);

        $chat = Chat::create(['type' => 'private']);
        $chat->participants()->attach([$user1->id, $user2->id]);

        $intruderToken = $intruder->createToken('intruder_token')->plainTextToken;

        // Try sending message
        $sendResponse = $this->withToken($intruderToken)
                             ->postJson('/api/chats/' . $chat->id . '/messages', [
                                 'message_type' => 'text',
                                 'message_text' => 'I am intruding!',
                             ]);

        $sendResponse->assertStatus(403);

        // Try reading messages
        $readResponse = $this->withToken($intruderToken)
                             ->getJson('/api/chats/' . $chat->id . '/messages');

        $readResponse->assertStatus(403);
    }
}
