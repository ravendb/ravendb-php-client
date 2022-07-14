<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12030Test;

class Fox
{
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
