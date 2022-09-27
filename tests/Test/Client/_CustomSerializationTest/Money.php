<?php

namespace tests\RavenDB\Test\Client\_CustomSerializationTest;

class Money
{
    private ?string $currency = null;
    private ?int $amount = null;

    public function __construct(?int $amount = null, ?string $currency = null)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function __toString()
    {
        return $this->amount . ' ' . $this->currency;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): void
    {
        $this->amount = $amount;
    }

    public static function forDollars(?int $amount): Money
    {
        return new Money($amount, 'USD');
    }

    public static function forEuro(?int $amount): Money
    {
        return new Money($amount, 'EUR');
    }
}
