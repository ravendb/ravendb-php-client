<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class Product2
{
    private ?string $category = null;
    private ?string $name = null;
    private ?int $pricePerUnit = null;

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPricePerUnit(): ?int
    {
        return $this->pricePerUnit;
    }

    public function setPricePerUnit(?int $pricePerUnit): void
    {
        $this->pricePerUnit = $pricePerUnit;
    }

    public static function create(?string $category, ?string $name, ?int $pricePerUnit)
    {
            $product = new Product2();
            $product->setCategory($category);
            $product->setName($name);
            $product->setPricePerUnit($pricePerUnit);
            return $product;
        }
}
