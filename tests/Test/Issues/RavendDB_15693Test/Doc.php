<?php

namespace tests\RavenDB\Test\Issues\RavendDB_15693Test;

class Doc
{
    private ?string $strVal1 = null;
    private ?string $strVal2 = null;
    private ?string $strVal3 = null;

    public function getStrVal1(): ?string
    {
        return $this->strVal1;
    }

    public function setStrVal1(?string $strVal1): void
    {
        $this->strVal1 = $strVal1;
    }

    public function getStrVal2(): ?string
    {
        return $this->strVal2;
    }

    public function setStrVal2(?string $strVal2): void
    {
        $this->strVal2 = $strVal2;
    }

    public function getStrVal3(): ?string
    {
        return $this->strVal3;
    }

    public function setStrVal3(?string $strVal3): void
    {
        $this->strVal3 = $strVal3;
    }
}
