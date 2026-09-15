<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chat_id' => $this->chat_id,
            'sender_id' => $this->sender_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'message_type' => $this->message_type,
            'message_text' => $this->message_text,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'status' => $this->status ?? 'read',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
