<?php

namespace tests\RavenDB\Test\Issues\RavenDB_13034Test;

class User
{
    public ?string $name = null;
    public ?int $age = null;

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
}
