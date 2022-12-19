<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateTimeInterface;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;

class TimeSeriesRangeResult
{
    private ?DateTimeInterface $from = null;
    private ?DateTimeInterface $to = null;
    private ?TimeSeriesEntryArray $entries = null;
    private ?int $totalResults = null;
    private ?array $includes = null;

    public function getFrom(): ?DateTimeInterface
    {
        return $this->from;
    }

    public function setFrom(?DateTimeInterface $from): void
    {
        $this->from = $from;
    }

    public function getTo(): ?DateTimeInterface
    {
        return $this->to;
    }

    public function setTo(?DateTimeInterface $to): void
    {
        $this->to = $to;
    }

    public function getEntries(): ?TimeSeriesEntryArray
    {
        return $this->entries;
    }

    public function setEntries(?TimeSeriesEntryArray $entries): void
    {
        $this->entries = $entries;
    }

    public function getTotalResults(): ?int
    {
        return $this->totalResults;
    }

    public function setTotalResults(?int $totalResults): void
    {
        $this->totalResults = $totalResults;
    }

    public function getIncludes(): ?array
    {
        return $this->includes;
    }

    public function setIncludes(?array $includes): void
    {
        $this->includes = $includes;
    }
}
