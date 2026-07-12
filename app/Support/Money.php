<?php

namespace App\Support;

use Brick\Money\Money as BrickMoney;

/**
 * Thin wrapper around brick/money enforcing the OneMarket247 money standard:
 * all monetary values are stored/passed as integer minor units and never as
 * floats (see docs/architecture/01-system-architecture.md §6). Models cast
 * money columns through this class rather than working with BrickMoney
 * directly, so the rest of the app has one Money type to depend on.
 */
final class Money
{
    private function __construct(private readonly BrickMoney $money) {}

    public static function ofMinor(int $minorUnits, string $currencyCode): self
    {
        return new self(BrickMoney::ofMinor($minorUnits, $currencyCode));
    }

    public static function zero(string $currencyCode): self
    {
        return self::ofMinor(0, $currencyCode);
    }

    public function add(self $other): self
    {
        return new self($this->money->plus($other->money));
    }

    public function subtract(self $other): self
    {
        return new self($this->money->minus($other->money));
    }

    public function isNegative(): bool
    {
        return $this->money->isNegative();
    }

    public function minorUnits(): int
    {
        return $this->money->getMinorAmount()->toInt();
    }

    public function currencyCode(): string
    {
        return $this->money->getCurrency()->getCurrencyCode();
    }

    public function format(): string
    {
        return $this->money->formatTo(app()->getLocale());
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
