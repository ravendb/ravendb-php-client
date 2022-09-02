<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class Category
{
    private ?string $description = null;
    private ?string $name = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
