<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'is_mine' => $this->sender_id === $request->user()?->id,
            'body' => $this->body,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
