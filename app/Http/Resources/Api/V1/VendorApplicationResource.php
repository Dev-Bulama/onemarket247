<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal receipt returned to a just-submitted vendor application — the
 * applicant has no account yet to fetch a richer view with, so this is
 * intentionally just enough to confirm what happened and let them
 * reference the application later (e.g. in a support request).
 */
class VendorApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'created_at' => $this->created_at,
        ];
    }
}
