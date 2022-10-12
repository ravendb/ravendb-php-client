<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15539Test;

class User
{
    private ?string $name = null;
    private bool $ignoreChanges = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function isIgnoreChanges(): bool
    {
        return $this->ignoreChanges;
    }

    public function setIgnoreChanges(bool $ignoreChanges): void
    {
        $this->ignoreChanges = $ignoreChanges;
    }
}
