<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'profile_image' => $this->profile_image ? url(Storage::url($this->profile_image)) : null,
            'created_by' => $this->created_by,
            'chat_id' => $this->chat?->id,
            'members' => UserResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
