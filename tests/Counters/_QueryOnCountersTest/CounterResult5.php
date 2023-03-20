<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class CounterResult5
{
    private ?array $counters = null;

    public function getCounters(): ?array
    {
        return $this->counters;
    }

    public function setCounters(?array $counters): void
    {
        $this->counters = $counters;
    }
}
