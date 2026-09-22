<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorProductVariationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => Money::make($this->price),
            'compare_at_price' => Money::make($this->compare_at_price),
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status->value,
            'is_active' => $this->is_active,
            'image' => $this->getFirstMediaUrl('images') ?: null,
            'attributes' => $this->attributeValues->map(fn ($value) => [
                'attribute_id' => $value->attribute_id,
                'attribute' => $value->attribute->name,
                'value_id' => $value->id,
                'value' => $value->value,
            ])->values(),
        ];
    }
}
