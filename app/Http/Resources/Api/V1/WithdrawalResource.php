<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->reference,
            'amount' => Money::make($this->amount),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'bank_name' => $this->whenLoaded('withdrawalMethod', fn () => $this->withdrawalMethod?->bank_name),
            'requested_at' => $this->created_at,
            'paid_at' => $this->paid_at,
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
