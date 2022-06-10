<?php

namespace tests\RavenDB\Test\Issues\RavenDB_11649Test;

class MiddleClass
{
    private ?InnerClass $a = null;

    public function getA(): ?InnerClass
    {
        return $this->a;
    }

    public function setA(?InnerClass $a): void
    {
        $this->a = $a;
    }
}
