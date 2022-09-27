<?php

namespace tests\RavenDB\Test\Server\Documents\Indexing\auto\_RavenDB_8761Test;

class ProductCount
{
    private ?string $productName = null;
    private ?int $count = null;
    private ?string $country = null;
    private ?int $quantity = null;
    private ?array $products = null;
    private ?array $quantities = null;

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(?string $productName): void
    {
        $this->productName = $productName;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getProducts(): ?array
    {
        return $this->products;
    }

    public function setProducts(?array $products): void
    {
        $this->products = $products;
    }

    public function getQuantities(): ?array
    {
        return $this->quantities;
    }

    public function setQuantities(?array $quantities): void
    {
        $this->quantities = $quantities;
    }
}
