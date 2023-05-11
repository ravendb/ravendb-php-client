<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15826Test;

class Item
{
    private ?array $refs = null;

    public function getRefs(): ?array
    {
        return $this->refs;
    }

    public function setRefs(?array $refs): void
    {
        $this->refs = $refs;
    }
}
