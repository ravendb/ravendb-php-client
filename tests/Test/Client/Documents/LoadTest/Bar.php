<?php

namespace tests\RavenDB\Test\Client\Documents\LoadTest;

class Bar
{
    private ?string $fooId = null;
    private array $fooIDs = [];
    private ?string $name = null;

    public function getFooId(): ?string
    {
        return $this->fooId;
    }

    public function setFooId(?string $fooId): void
    {
        $this->fooId = $fooId;
    }

    public function getFooIDs(): array
    {
        return $this->fooIDs;
    }

    public function setFooIDs(array $fooIDs): void
    {
        $this->fooIDs = $fooIDs;
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
