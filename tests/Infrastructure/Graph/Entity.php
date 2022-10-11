<?php

namespace tests\RavenDB\Infrastructure\Graph;

class Entity
{
    private ?string $id = null;
    private ?string $name = null;
    private ?string $references = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getReferences(): ?string
    {
        return $this->references;
    }

    public function setReferences(?string $references): void
    {
        $this->references = $references;
    }
}
