<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class CounterResult3
{
    private ?array $downloads = null;

    public function getDownloads(): ?array
    {
        return $this->downloads;
    }

    public function setDownloads(?array $downloads): void
    {
        $this->downloads = $downloads;
    }

}
