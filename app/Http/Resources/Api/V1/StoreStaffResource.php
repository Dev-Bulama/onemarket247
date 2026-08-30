<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreStaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'permissions' => $this->whenLoaded('user', fn () => $this->user->getPermissionNames()->all()),
            'invited_at' => $this->invited_at,
            'joined_at' => $this->joined_at,
        ];
    }
}
