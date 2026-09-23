<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal receipt returned to a just-submitted agent application — mirrors
 * VendorApplicationResource's rationale exactly.
 */
class AgentApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'created_at' => $this->created_at,
        ];
    }
}
