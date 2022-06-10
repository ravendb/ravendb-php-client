<?php

namespace tests\RavenDB\Test\Client\_WhatChangedTest;

class FloatNumber
{
    private float $number;

    public function getNumber(): float
    {
        return $this->number;
    }

    public function setNumber(float $number): void
    {
        $this->number = $number;
    }
}
