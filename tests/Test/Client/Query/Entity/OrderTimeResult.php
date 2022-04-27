<?php

namespace tests\RavenDB\Test\Client\Query\Entity;

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
