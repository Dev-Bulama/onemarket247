<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The vendor's own view of their product — unlike ProductResource (the
 * public storefront shape), this includes status/rejection info a
 * customer should never see.
 */
class VendorProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'rejection_reason' => $this->rejection_reason,
            'thumbnail' => $this->getFirstMediaUrl('images', 'thumb') ?: $this->getFirstMediaUrl('images') ?: null,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => Money::make($this->price),
            'compare_at_price' => Money::make($this->compare_at_price),
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status->value,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,
        ];
    }
}
