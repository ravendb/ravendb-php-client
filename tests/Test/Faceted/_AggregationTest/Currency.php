<?php

namespace tests\RavenDB\Test\Faceted\_AggregationTest;

use RavenDB\Type\ValueObjectInterface;

class Currency implements ValueObjectInterface
{
    private const EUR = 'Eur';
    private const PLN = 'Pln';
    private const NIS = 'Nis';

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

    public function isEur(): bool
    {
        return $this->value == self::EUR;
    }

    public static function eur(): Currency
    {
        return new Currency(self::EUR);
    }

    public function isPln(): bool
    {
        return $this->value == self::PLN;
    }

    public static function pln(): Currency
    {
        return new Currency(self::PLN);
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
