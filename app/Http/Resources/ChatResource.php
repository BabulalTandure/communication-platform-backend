<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;

        // For private chats, identify the recipient user
        $recipient = null;
        if ($this->type === 'private' && $this->relationLoaded('participants')) {
            $otherUser = $this->participants->firstWhere('id', '!=', $currentUserId);
            if ($otherUser) {
                $recipient = new UserResource($otherUser);
            }
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'group' => $this->when($this->type === 'group', new GroupResource($this->whenLoaded('group'))),
            'recipient' => $this->when($this->type === 'private', $recipient),
            'participants' => UserResource::collection($this->whenLoaded('participants')),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
