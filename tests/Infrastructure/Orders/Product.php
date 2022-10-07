<?php

namespace tests\RavenDB\Infrastructure\Orders;

class Product
{
    private ?string $id = null;
    private ?string $name = null;
    private ?string $supplier = null;
    private ?string $category = null;
    private ?string $quantityPerUnit = null;
    private ?float $pricePerUnit = null;
    private ?int $unitsInStock = null;
    private ?int $unitsOnOrder = null;
    private bool $discontinued = false;
    private ?int $reorderLevel = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(?string $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getQuantityPerUnit(): ?string
    {
        return $this->quantityPerUnit;
    }

    public function setQuantityPerUnit(?string $quantityPerUnit): void
    {
        $this->quantityPerUnit = $quantityPerUnit;
    }

    public function getPricePerUnit(): ?float
    {
        return $this->pricePerUnit;
    }

    public function setPricePerUnit(?float $pricePerUnit): void
    {
        $this->pricePerUnit = $pricePerUnit;
    }

    public function getUnitsInStock(): ?int
    {
        return $this->unitsInStock;
    }

    public function setUnitsInStock(?int $unitsInStock): void
    {
        $this->unitsInStock = $unitsInStock;
    }

    public function getUnitsOnOrder(): ?int
    {
        return $this->unitsOnOrder;
    }

    public function setUnitsOnOrder(?int $unitsOnOrder): void
    {
        $this->unitsOnOrder = $unitsOnOrder;
    }

    public function isDiscontinued(): bool
    {
        return $this->discontinued;
    }

    public function setDiscontinued(bool $discontinued): void
    {
        $this->discontinued = $discontinued;
    }

    public function getReorderLevel(): ?int
    {
        return $this->reorderLevel;
    }

    public function setReorderLevel(?int $reorderLevel): void
    {
        $this->reorderLevel = $reorderLevel;
    }
}
