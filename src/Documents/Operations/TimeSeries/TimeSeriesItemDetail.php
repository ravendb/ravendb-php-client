<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateTimeInterface;

class TimeSeriesItemDetail
{
    private ?string $name = null;
    private ?int $numberOfEntries = null;
    private ?DateTimeInterface $startDate = null;
    private ?DateTimeInterface $endDate = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getNumberOfEntries(): ?int
    {
        return $this->numberOfEntries;
    }

    public function setNumberOfEntries(?int $numberOfEntries): void
    {
        $this->numberOfEntries = $numberOfEntries;
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): void
    {
        $this->endDate = $endDate;
    }
}
