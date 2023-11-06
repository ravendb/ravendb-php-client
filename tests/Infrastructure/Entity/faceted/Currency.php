<?php

namespace tests\RavenDB\Infrastructure\Entity\faceted;

use RavenDB\Type\ValueObjectInterface;

class Currency implements ValueObjectInterface
{
    private const USD = 'USD';
    private const EUR = 'EUR';
    private const NIS = 'NIS';

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isUsd(): bool
    {
        return $this->value == self::USD;
    }

    public static function usd(): Currency
    {
        return new Currency(self::USD);
    }

    public function isEur(): bool
    {
        return $this->value == self::EUR;
    }

    public static function eur(): Currency
    {
        return new Currency(self::EUR);
    }

    public function isNis(): bool
    {
        return $this->value == self::NIS;
    }

    public static function nis(): Currency
    {
        return new Currency(self::NIS);
    }
}
