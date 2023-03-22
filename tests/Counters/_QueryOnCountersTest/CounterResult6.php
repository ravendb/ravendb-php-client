<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class CounterResult6
{
    private ?int $counter = null;

    public function getCounter(): ?int
    {
        return $this->counter;
    }

    public function setCounter(?int $counter): void
    {
        $this->counter = $counter;
    }
}
