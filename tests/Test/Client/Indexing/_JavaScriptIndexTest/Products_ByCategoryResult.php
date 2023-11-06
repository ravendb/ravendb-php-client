<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class Products_ByCategoryResult
{
    private ?string $category = null;
    private ?int $count = null;

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }
}
