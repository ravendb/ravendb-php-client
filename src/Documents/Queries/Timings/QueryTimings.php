<?php

namespace RavenDB\Documents\Queries\Timings;

use RavenDB\Documents\Queries\QueryResult;

class QueryTimings
{
    private int $durationInMs = 0;
    private ?QueryTimingsArray $timings = null;

    public function __construct()
    {
        $this->timings = new QueryTimingsArray();
    }

    public function getDurationInMs(): int
    {
        return $this->durationInMs;
    }

    public function setDurationInMs(int $durationInMs): void
    {
        $this->durationInMs = $durationInMs;
    }

    public function getTimings(): ?QueryTimingsArray
    {
        return $this->timings;
    }

    public function setTimings(?QueryTimingsArray $timings): void
    {
        $this->timings = $timings;
    }

    public function update(QueryResult $queryResult): void
    {
        $this->durationInMs = 0;
        $this->timings = null;

        if ($queryResult->getTimings() == null) {
            return;
        }

        $this->durationInMs = $queryResult->getTimings()->getDurationInMs();
        $this->timings = $queryResult->getTimings()->getTimings();
    }
}
