<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14164Test;

class Watch
{
    private ?string $name = null;
    private ?float $accuracy = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAccuracy(): ?float
    {
        return $this->accuracy;
    }

    public function setAccuracy(?float $accuracy): void
    {
        $this->accuracy = $accuracy;
    }
}
