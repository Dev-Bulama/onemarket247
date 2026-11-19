<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // The product can be soft-deleted after already sitting in a
            // cart (see CartItem::currentUnitPrice()'s note) — null-safe
            // throughout so a stale cart item never 500s this endpoint,
            // just comes back with nulled-out product fields.
            'product' => [
                'id' => $this->product?->id,
                'slug' => $this->product?->slug,
                'name' => $this->product?->translatedName(),
                'thumbnail' => $this->product ? ($this->product->getFirstMediaUrl('images', 'thumb') ?: $this->product->getFirstMediaUrl('images') ?: null) : null,
                'vendor_store' => $this->product?->vendor?->store?->name,
            ],
            'variation' => $this->whenLoaded('variation', fn () => $this->variation ? [
                'id' => $this->variation->id,
                'attributes' => $this->variation->attributeValues->map(fn ($value) => [
                    'attribute' => $value->attribute->name,
                    'value' => $value->value,
                ])->values(),
            ] : null),
            'quantity' => $this->quantity,
            'unit_price' => Money::make($this->unit_price),
            'line_total' => Money::make($this->lineTotal()),
            'in_stock' => $this->isInStock(),
            'price_drifted' => $this->hasPriceDrifted(),
            'saved_for_later' => $this->saved_for_later,
        ];
    }
}
