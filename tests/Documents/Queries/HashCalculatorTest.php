<?php

namespace tests\RavenDB\Documents\Queries;

use RavenDB\Documents\Queries\HashCalculator;
use tests\RavenDB\RemoteTestBase;

class HashCalculatorTest extends RemoteTestBase
{
    public function testCalculatesTheSameHashForTheSameObject() : void
    {
        $obj = new \stdClass();
        $obj->boolean = true;
        // !!! Serialization of Closure is not allowed !!!
//        $obj->function = function() {
//            $this->assertTrue(true); // noop
//        };
        $obj->number = 4;

        $otherObject = new \stdClass();
        $otherObject->property = 'value';

        $obj->object = $otherObject;
        $obj->string = 'hello';
        $obj->null = null;

        $this->assertEquals($this->hash($obj), $this->hash($obj));

        $objClone = clone $obj;
        $this->assertEquals($this->hash($obj), $this->hash($objClone));
    }

    public function testCalculatesDifferentHashesForDifferentTypes(): void
    {
        $this->assertNotEquals($this->hash(1), $this->hash(true));
        $this->assertNotEquals($this->hash('1'), $this->hash(true));
        $this->assertNotEquals($this->hash(1), $this->hash('1'));

        $this->assertNotEquals($this->hash(null), $this->hash(0));
        $this->assertNotEquals($this->hash(null), $this->hash(false));
        $this->assertNotEquals($this->hash(false), $this->hash(0));
    }

    public function testCalculatesDifferentHashesForDifferentNumbers(): void
    {
        $this->assertNotEquals($this->hash(1), $this->hash(257));
        $this->assertNotEquals($this->hash(86400), $this->hash(0));
    }

    protected function hash($value): string
    {
        $calculator = new HashCalculator();
        $calculator->write($value);
        return $calculator->getHash();
    }
}
