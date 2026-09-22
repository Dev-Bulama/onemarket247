<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The minimal vendor shape needed for the "Store / Vendor" filter checkbox
 * list — see FiltersProducts::filterOptions(). Deliberately not the full
 * VendorResource, which exposes far more than a filter option needs.
 */
class VendorFilterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->business_name,
        ];
    }
}
