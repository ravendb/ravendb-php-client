<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\ResultInterface;

// !status: DONE
class GetDocumentsResult implements ResultInterface
{
    private array $includes = [];
    private array $results = [];

    private array $counterIncludes = [];
    private array $timeSeriesIncludes = [];
    private array $compareExchangeValueIncludes = [];
    private int $nextPageStart = 0;

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getIncludes(): array
    {
        return $this->includes;
    }

    public function setIncludes(array $includes): void
    {
        $this->includes = $includes;
    }

    public function getCounterIncludes(): array
    {
        return $this->counterIncludes;
    }

    public function setCounterIncludes(array $counterIncludes): void
    {
        $this->counterIncludes = $counterIncludes;
    }

    public function getTimeSeriesIncludes(): array
    {
        return $this->timeSeriesIncludes;
    }

    public function setTimeSeriesIncludes(array $timeSeriesIncludes): void
    {
        $this->timeSeriesIncludes = $timeSeriesIncludes;
    }

    public function getCompareExchangeValueIncludes(): array
    {
        return $this->compareExchangeValueIncludes;
    }

    public function setCompareExchangeValueIncludes(array $compareExchangeValueIncludes): void
    {
        $this->compareExchangeValueIncludes = $compareExchangeValueIncludes;
    }

    public function getNextPageStart(): int
    {
        return $this->nextPageStart;
    }

    public function setNextPageStart(int $nextPageStart): void
    {
        $this->nextPageStart = $nextPageStart;
    }
}
