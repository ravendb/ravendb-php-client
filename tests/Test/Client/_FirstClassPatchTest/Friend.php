<?php

namespace tests\RavenDB\Test\Client\_FirstClassPatchTest;

class Friend
{
    private ?string $name = null;
    private ?int $age = null;
    private ?Pet $pet = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): void
    {
        $this->age = $age;
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(?Pet $pet): void
    {
        $this->pet = $pet;
    }
}
