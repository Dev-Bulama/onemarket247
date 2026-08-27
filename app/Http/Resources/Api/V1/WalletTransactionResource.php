<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'balance_bucket' => $this->balance_bucket->value,
            'amount' => Money::make($this->amount),
            'order_number' => $this->whenLoaded('vendorOrder', fn () => $this->vendorOrder?->vendor_order_number),
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}
