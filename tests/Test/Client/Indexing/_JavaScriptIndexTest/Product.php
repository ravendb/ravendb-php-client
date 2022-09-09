<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class Product
{
    private ?string $name = null;
    private bool $available = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }
}
