<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'checkout_session_key' => $this->idempotency_key,
            'subtotal' => Money::make($this->subtotal),
            'discount_amount' => Money::make($this->discount_amount),
            'total' => Money::make($this->total),
            'expires_at' => $this->expires_at,
            'is_expired' => $this->isExpired(),
            'is_resolved' => $this->isResolved(),
            'order' => $this->whenLoaded('order', fn () => $this->order ? new OrderResource($this->order) : null),
        ];
    }
}
