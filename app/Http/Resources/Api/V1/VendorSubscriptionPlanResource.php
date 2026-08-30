<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorSubscriptionPlanResource extends JsonResource
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
            'description' => $this->description,
            'price' => Money::make($this->price),
            'is_free' => $this->isFree(),
            'billing_period' => $this->billing_period?->value,
            'max_products' => $this->max_products,
            'features' => $this->features,
            'is_default' => $this->is_default,
        ];
    }
}
