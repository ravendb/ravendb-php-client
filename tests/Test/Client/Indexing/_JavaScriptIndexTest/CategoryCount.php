<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class CategoryCount
{
    private ?string $category = null;
    private ?float $count = null;

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getCount(): ?float
    {
        return $this->count;
    }

    public function setCount(?float $count): void
    {
        $this->count = $count;
    }

}
