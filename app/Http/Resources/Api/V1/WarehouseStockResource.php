<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ] : null),
            'variation_id' => $this->product_variation_id,
            'on_hand' => $this->on_hand,
            'reserved' => $this->reserved,
            'damaged' => $this->damaged,
            'incoming' => $this->incoming,
            'available' => $this->available(),
        ];
    }
}
