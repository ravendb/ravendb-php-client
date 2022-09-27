<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14272Test;

class TalkUserDef
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
