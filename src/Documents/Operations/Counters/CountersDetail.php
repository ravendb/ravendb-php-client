<?php

namespace RavenDB\Documents\Operations\Counters;

class CountersDetail
{
    private ?CounterDetailList $counters = null;

    public function __construct()
    {
        $this->counters = new CounterDetailList();
    }

    public function getCounters(): ?CounterDetailList
    {
        return $this->counters;
    }

    public function setCounters(?CounterDetailList $counters): void
    {
        $this->counters = $counters;
    }
}
