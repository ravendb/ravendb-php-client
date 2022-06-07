<?php

namespace tests\RavenDB\Test\Client\_WhatChangedTest;

class Arr
{
    private array $array = [];

    public function getArray(): array
    {
        return $this->array;
    }

    public function setArray(array $array): void
    {
        $this->array = $array;
    }
}
