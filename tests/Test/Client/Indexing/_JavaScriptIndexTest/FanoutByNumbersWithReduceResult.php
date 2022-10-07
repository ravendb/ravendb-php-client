<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class FanoutByNumbersWithReduceResult
{
    private ?string $foo = null;
    private ?int $sum = null;

    public function getFoo(): ?string
    {
        return $this->foo;
    }

    public function setFoo(?string $foo): void
    {
        $this->foo = $foo;
    }

    public function getSum(): ?int
    {
        return $this->sum;
    }

    public function setSum(?int $sum): void
    {
        $this->sum = $sum;
    }
}
