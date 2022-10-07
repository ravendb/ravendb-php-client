<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9745Test;

class Companies_ByNameResult
{
    private ?string $key = null;
    private ?int $count = null;

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }
}
