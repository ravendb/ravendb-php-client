<?php

namespace tests\RavenDB\Test\Client\_WhatChangedTest;

class IntNumber
{
    // Alex and Marcin agreed to remove strong type to int from $number in this test class,
    // because it'll cause test WhatChangedTest::test_ravenDB_8169 to fail
    // PHP as a language is preventing assignment of number like 2.0 to strongly typed int variable
    // and that is exactly what is going on in this test
    private $number;

    public function getNumber()
    {
        return $this->number;
    }

    public function setNumber($number): void
    {
        $this->number = intval($number);
    }
}
