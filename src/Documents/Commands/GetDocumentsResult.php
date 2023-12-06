<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\ResultInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class GetDocumentsResult implements ResultInterface
{
    /** @SerializedName ("Includes") */
    private array $includes = [];

    /** @SerializedName ("Results") */
    private array $results = [];

    /** @SerializedName ("CounterIncludes") */
    private array $counterIncludes = [];

    /** @SerializedName ("RevisionIncludes") */
    private array $revisionIncludes = [];

    /** @SerializedName ("TimeSeriesIncludes") */
    private array $timeSeriesIncludes = [];

    /** @SerializedName ("CompareExchangeValueIncludes") */
    private array $compareExchangeValueIncludes = [];

    /** @SerializedName ("NextPageStart") */
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

    public function getRevisionIncludes(): array
    {
        return $this->revisionIncludes;
    }

    public function setRevisionIncludes(array $revisionIncludes): void
    {
        $this->revisionIncludes = $revisionIncludes;
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
