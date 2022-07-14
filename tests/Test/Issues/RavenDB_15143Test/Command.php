<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15143Test;

class Command
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
