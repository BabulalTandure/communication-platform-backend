<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\CreatePrivateChatRequest;
use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $chats = $request->user()->chats()
            ->with(['participants', 'group', 'latestMessage.sender'])
            ->latest('updated_at')
            ->get();

        return $this->successResponse(ChatResource::collection($chats), 'Chats retrieved successfully.');
    }

    public function createPrivate(CreatePrivateChatRequest $request): JsonResponse
    {
        $currentUserId = $request->user()->id;
        $recipientId = (int) $request->recipient_id;

        // Check if recipient is active
        $recipient = User::find($recipientId);
        if (!$recipient || !$recipient->isActive()) {
            return $this->errorResponse('Recipient user not found or inactive.', null, 404);
        }

        // Find existing private chat between these two users
        $existingChat = Chat::where('type', 'private')
            ->whereHas('participants', fn($q) => $q->where('users.id', $currentUserId))
            ->whereHas('participants', fn($q) => $q->where('users.id', $recipientId))
            ->first();

        if ($existingChat) {
            $existingChat->load(['participants', 'latestMessage.sender']);
            return $this->successResponse(new ChatResource($existingChat), 'Private chat retrieved successfully.');
        }

        // Create new private chat
        $chat = DB::transaction(function () use ($currentUserId, $recipientId) {
            $chat = Chat::create([
                'type' => 'private',
            ]);

            $chat->participants()->attach([$currentUserId, $recipientId]);

            return $chat;
        });

        $chat->load(['participants', 'latestMessage.sender']);

        return $this->successResponse(new ChatResource($chat), 'Private chat created successfully.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $chat = Chat::with(['participants', 'group', 'latestMessage.sender'])->find($id);

        if (!$chat) {
            return $this->errorResponse('Chat not found.', null, 404);
        }

        if (!$chat->isParticipant($request->user()->id)) {
            return $this->errorResponse('Unauthorized access to this conversation.', null, 403);
        }

        return $this->successResponse(new ChatResource($chat), 'Chat details retrieved successfully.');
    }
}
