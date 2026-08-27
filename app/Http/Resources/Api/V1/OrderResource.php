<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\PaymentStatus;
use App\Models\Setting;
use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->relationLoaded('payments') ? $this->payments->sortByDesc('id')->first() : null;

        return [
            'id' => $this->public_id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'placed_at' => $this->placed_at,
            'subtotal' => Money::make($this->subtotal),
            'discount_amount' => Money::make($this->discount_amount),
            'shipping_amount' => Money::make($this->shipping_amount),
            'tax_amount' => Money::make($this->tax_amount),
            'total' => Money::make($this->total),
            'coupon_code' => $this->coupon_code,
            'shipping_address' => [
                'full_name' => $this->shipping_full_name,
                'phone' => $this->shipping_phone,
                'address_line_1' => $this->shipping_address_line_1,
                'address_line_2' => $this->shipping_address_line_2,
                'city' => $this->whenLoaded('shippingCity', fn () => $this->shippingCity?->name),
                'state' => $this->whenLoaded('shippingState', fn () => $this->shippingState?->name),
                'country' => $this->whenLoaded('shippingCountry', fn () => $this->shippingCountry?->name),
                'postal_code' => $this->shipping_postal_code,
            ],
            'payment' => $payment ? [
                'status' => $payment->status->value,
                'gateway' => $payment->gateway,
                'paid_at' => $payment->paid_at,
            ] : null,
            'bank_transfer' => ($payment && $payment->gateway === 'bank_transfer' && $payment->status === PaymentStatus::Pending)
                ? [
                    'bank_name' => Setting::where('key', 'payment.bank_transfer.bank_name')->value('value'),
                    'account_name' => Setting::where('key', 'payment.bank_transfer.account_name')->value('value'),
                    'account_number' => Setting::where('key', 'payment.bank_transfer.account_number')->value('value'),
                    'reference' => $this->order_number,
                ]
                : null,
            'vendor_orders' => $this->whenLoaded('vendorOrders', fn () => VendorOrderResource::collection($this->vendorOrders)),
        ];
    }
}
