<?php

namespace tests\RavenDB\Test\Faceted\_AggregationTest;

class Order
{
    private ?Currency $currency = null;
    private ?string $product = null;
    private ?float $total = null;
    private ?int $region = null;

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getProduct(): ?string
    {
        return $this->product;
    }

    public function setProduct(?string $product): void
    {
        $this->product = $product;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(?float $total): void
    {
        $this->total = $total;
    }

    public function getRegion(): ?int
    {
        return $this->region;
    }

    public function setRegion(?int $region): void
    {
        $this->region = $region;
    }
}
