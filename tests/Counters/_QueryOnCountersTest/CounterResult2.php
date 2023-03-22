<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class CounterResult2
{
    private ?int $downloadsCount = null;
    private ?int $likesCount = null;

    public function getDownloadsCount(): ?int
    {
        return $this->downloadsCount;
    }

    public function setDownloadsCount(?int $downloadsCount): void
    {
        $this->downloadsCount = $downloadsCount;
    }

    public function getLikesCount(): ?int
    {
        return $this->likesCount;
    }

    public function setLikesCount(?int $likesCount): void
    {
        $this->likesCount = $likesCount;
    }
}
