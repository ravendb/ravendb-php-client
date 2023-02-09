<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateTimeInterface;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;
use RavenDB\Primitives\NetISO8601Utils;

class TimeSeriesRangeResult
{
    private ?DateTimeInterface $from = null;
    private ?DateTimeInterface $to = null;
    private ?TimeSeriesEntryArray $entries = null;
    private ?int $totalResults = null;
    private ?array $includes = null;

    public function __construct(?array $data = null)
    {
        if (!empty($data)) {
            $this->initFromArray($data);
        }
    }

    private function initFromArray (array $data): void
    {
        if (array_key_exists('From', $data)) {
            $this->from = is_string($data['From']) ? NetISO8601Utils::fromString($data['From']) : $data['From'];
        }
        if (array_key_exists('To', $data)) {
            $this->to = is_string($data['To']) ? NetISO8601Utils::fromString($data['To']) : $data['To'];
        }
        if (array_key_exists('Entries', $data)) {
            $this->entries = TimeSeriesEntryArray::fromArray($data['Entries']);
        }
    }

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
