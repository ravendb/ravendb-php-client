<?php

namespace tests\RavenDB\Test\Issues\RDBC_501Test;

class MarkerSymbol
{
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }
}
