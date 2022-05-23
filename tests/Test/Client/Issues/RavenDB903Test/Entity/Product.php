<?php

namespace tests\RavenDB\Test\Client\Issues\RavenDB903Test\Entity;

class Product
{
    private ?string $name = null;
    private ?string $description = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
