<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Conversation $resource
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor?->store ? [
                'id' => $this->vendor->id,
                'store_name' => $this->vendor->store->name,
                'store_slug' => $this->vendor->store->slug,
            ] : null),
            'with_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ] : null),
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? [
                'body' => $this->latestMessage->body,
                'sender_id' => $this->latestMessage->sender_id,
                'created_at' => $this->latestMessage->created_at,
            ] : null),
            'unread_count' => $this->unread_count ?? 0,
            'is_closed' => $this->isClosed(),
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
        ];
    }
}
