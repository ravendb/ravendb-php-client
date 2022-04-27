<?php

namespace tests\RavenDB\Infrastructure\Entity\faceted;

use DateTimeInterface;

// !status: DONE
class Order
{
    private ?string $product = null;
    private ?float $total = null;
    private ?Currency $currency = null;
    private ?int $quantity = null;
    private ?int $region = null;
    private ?DateTimeInterface $at = null;
    private ?float $tax = null;

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

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getRegion(): ?int
    {
        return $this->region;
    }

    public function setRegion(?int $region): void
    {
        $this->region = $region;
    }

    public function getAt(): ?DateTimeInterface
    {
        return $this->at;
    }

    public function setAt(?DateTimeInterface $at): void
    {
        $this->at = $at;
    }

    public function getTax(): ?float
    {
        return $this->tax;
    }

    public function setTax(?float $tax): void
    {
        $this->tax = $tax;
    }
}
