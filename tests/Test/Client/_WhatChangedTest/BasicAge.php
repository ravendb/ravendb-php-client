<?php

namespace tests\RavenDB\Test\Client\_WhatChangedTest;

class BasicAge
{
    private int $age = 0;

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }
}
