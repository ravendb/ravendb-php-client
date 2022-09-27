<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9889Test;

class Item
{
    private ?string $id = null;
    private bool $before = false;
    private bool $after = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function isBefore(): bool
    {
        return $this->before;
    }

    public function setBefore(bool $before): void
    {
        $this->before = $before;
    }

    public function isAfter(): bool
    {
        return $this->after;
    }

    public function setAfter(bool $after): void
    {
        $this->after = $after;
    }
}
