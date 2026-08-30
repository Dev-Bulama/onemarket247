<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'is_verified' => $this->is_verified,
            'is_featured' => $this->is_featured,
            'address' => $this->address,
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'state' => $this->whenLoaded('state', fn () => $this->state?->name),
            'country' => $this->whenLoaded('country', fn () => $this->country?->name),
            'working_hours' => $this->working_hours,
            'vacation_message' => $this->vacation_message,
        ];
    }
}
