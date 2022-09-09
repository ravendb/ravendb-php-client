<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class Fanout
{
    private ?string $foo = null;
    private array $numbers = [];

    public function getFoo(): ?string
    {
        return $this->foo;
    }

    public function setFoo(?string $foo): void
    {
        $this->foo = $foo;
    }

    public function getNumbers(): array
    {
        return $this->numbers;
    }

    public function setNumbers(array $numbers): void
    {
        $this->numbers = $numbers;
    }
}
