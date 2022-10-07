<?php

namespace tests\RavenDB\Bugs\_SimpleMultiMapTest;

class Dog implements IHaveName
{
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
