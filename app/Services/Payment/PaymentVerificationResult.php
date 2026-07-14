<?php

namespace App\Services\Payment;

use Illuminate\Support\Carbon;

class PaymentVerificationResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $successful,
        public readonly string $reference,
        public readonly int $amount,
        public readonly ?Carbon $paidAt,
        public readonly ?string $gatewayMessage,
        public readonly array $raw,
    ) {}
}
