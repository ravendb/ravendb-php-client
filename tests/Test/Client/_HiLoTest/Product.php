<?php

namespace tests\RavenDB\Test\Client\_HiLoTest;

class Product
{
    private ?string $productName = null;

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(?string $productName): void
    {
        $this->productName = $productName;
    }
}
