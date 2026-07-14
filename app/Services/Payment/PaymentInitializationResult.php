<?php

namespace App\Services\Payment;

class PaymentInitializationResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $authorizationUrl,
        public readonly string $reference,
        public readonly array $raw,
    ) {}
}
