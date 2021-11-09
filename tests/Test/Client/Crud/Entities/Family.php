<?php

namespace tests\RavenDB\Test\Client\Crud\Entities;

class Family
{
    private array $names = [];

    public function getNames(): array
    {
        return $this->names;
    }

    public function setNames(array $names): void
    {
        $this->names = $names;
    }
}
