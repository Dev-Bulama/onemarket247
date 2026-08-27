<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * $cart->session_token is only meaningful (and only ever non-null) for a
 * guest cart — a mobile client with no account yet must persist it
 * locally and replay it as `cart_token` on every subsequent cart call.
 * See App\Support\Cart\CartResolver's docblock.
 */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $vendorGroups = $this->activeItems->groupBy(fn ($item) => $item->product->vendor_id)
            ->map(fn ($items) => [
                'store_name' => $items->first()->product->vendor?->store?->name,
                'items' => CartItemResource::collection($items->values()),
            ])->values();

        return [
            'guest_token' => $this->session_token,
            'items' => CartItemResource::collection($this->activeItems),
            'saved_items' => CartItemResource::collection($this->savedItems),
            'vendor_groups' => $vendorGroups,
            'coupon' => $this->coupon ? [
                'code' => $this->coupon->code,
                'discount' => Money::make($this->coupon->discount_amount),
            ] : null,
            'subtotal' => Money::make($this->subtotal()),
            'discount' => Money::make($this->discount()),
            'total' => Money::make($this->total()),
        ];
    }
}
