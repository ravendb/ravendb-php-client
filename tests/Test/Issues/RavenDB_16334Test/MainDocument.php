<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16334Test;

class MainDocument
{
    private ?string $name = null;
    private ?string $id = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }
}
