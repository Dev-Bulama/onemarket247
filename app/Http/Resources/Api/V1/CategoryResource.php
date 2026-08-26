<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'icon' => $this->displayIcon(),
            'image' => $this->getFirstMediaUrl('image') ?: null,
            'children' => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),
        ];
    }
}
