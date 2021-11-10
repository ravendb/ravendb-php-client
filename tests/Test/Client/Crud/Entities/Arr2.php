<?php

namespace tests\RavenDB\Test\Client\Crud\Entities;

class Arr2
{
    private array $arr1;

    public function getArr1(): array
    {
        return $this->arr1;
    }

    public function setArr1(array $arr1): void
    {
        $this->arr1 = $arr1;
    }
}
