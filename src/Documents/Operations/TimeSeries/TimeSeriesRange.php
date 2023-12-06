<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateTimeInterface;

class TimeSeriesRange extends AbstractTimeSeriesRange
{
    private ?DateTimeInterface $from;
    private ?DateTimeInterface $to;

    public function __construct(?string $name = null, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null)
    {
        $this->setName($name);
        $this->from = $from;
        $this->to = $to;
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
}
