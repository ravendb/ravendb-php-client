<?php

namespace tests\RavenDB\Test\Client\_FirstClassPatchTest;

class Pet
{
    private ?string $name = null;
    private ?string $kind = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function setKind(?string $kind): void
    {
        $this->kind = $kind;
    }
}
