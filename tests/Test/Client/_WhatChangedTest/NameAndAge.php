<?php

namespace tests\RavenDB\Test\Client\_WhatChangedTest;

class NameAndAge
{
    private ?string $name = null;
    private int $age = 0;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }
}
