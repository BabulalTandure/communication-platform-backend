<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Message\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use ApiResponse;

    public function index(Request $request, int $chatId): JsonResponse
    {
        $chat = Chat::find($chatId);

        if (!$chat) {
            return $this->errorResponse('Chat not found.', null, 404);
        }

        if (!$chat->isParticipant($request->user()->id)) {
            return $this->errorResponse('Unauthorized access to this conversation.', null, 403);
        }

        $messages = $chat->messages()
            ->with('sender')
            ->latest()
            ->paginate(30);

        return $this->successResponse([
            'messages' => MessageResource::collection($messages->items()),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ], 'Messages retrieved successfully.');
    }

    public function store(SendMessageRequest $request, int $chatId): JsonResponse
    {
        $chat = Chat::find($chatId);

        if (!$chat) {
            return $this->errorResponse('Chat not found.', null, 404);
        }

        if (!$chat->isParticipant($request->user()->id)) {
            return $this->errorResponse('Unauthorized access to this conversation.', null, 403);
        }

        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $folder = $request->message_type === 'image' ? 'messages/images' : 'messages/voice_notes';

            $filePath = $file->store($folder, 'public');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $request->user()->id,
            'message_type' => $request->message_type,
            'message_text' => $request->message_text,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
        ]);

        $chat->touch();
        $message->load('sender');

        broadcast(new MessageSent($message))->toOthers();

        return $this->successResponse(new MessageResource($message), 'Message sent successfully.', 201);
    }
}
