<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'is_verified_purchase' => $this->is_verified_purchase,
            'vendor_response' => $this->vendor_response,
            'helpful_count' => $this->helpful_count,
            'created_at' => $this->created_at,
        ];
    }
}
