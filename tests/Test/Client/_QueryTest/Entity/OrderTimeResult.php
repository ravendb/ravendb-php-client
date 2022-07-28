<?php

namespace tests\RavenDB\Test\Client\_QueryTest\Entity;

class OrderTimeResult
{
    private int $delay = 0;

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }
}
