<?php

namespace App\Services\Payment;

class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $successful,
        public readonly int $amount,
        public readonly array $raw,
    ) {}
}
