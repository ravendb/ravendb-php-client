<?php

namespace tests\RavenDB\Test\Issues\RavenDB_11649Test;

class InnerClass
{
    private ?string $a = null;

    public function getA(): ?string
    {
        return $this->a;
    }

    public function setA(?string $a): void
    {
        $this->a = $a;
    }
}
