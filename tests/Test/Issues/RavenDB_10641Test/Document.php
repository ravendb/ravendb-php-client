<?php

namespace tests\RavenDB\Test\Issues\RavenDB_10641Test;

class Document
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
