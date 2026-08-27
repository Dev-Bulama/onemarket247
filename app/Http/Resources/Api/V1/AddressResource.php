<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'country' => $this->whenLoaded('country', fn () => $this->country?->name),
            'state' => $this->whenLoaded('state', fn () => $this->state?->name),
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'city_id' => $this->city_id,
            'postal_code' => $this->postal_code,
            'is_default_shipping' => $this->is_default_shipping,
            'is_default_billing' => $this->is_default_billing,
        ];
    }
}
