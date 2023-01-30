<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use DateTime;

/**
 * @template T
 */
class TypedTimeSeriesRangeAggregation
{
    /** @var T */
    private mixed $max;

    /** @var T */
    private mixed $min = null;

    /** @var T */
    private mixed $last = null;

    /** @var T */
    private mixed $first = null;

    /** @var T */
    private mixed $average = null;

    /** @var T */
    private mixed $sum = null;

    /** @var T */
    private mixed $count = null;

    private ?DateTime $to = null;

    private ?DateTime $from = null;

    public function getMax(): mixed
    {
        return $this->max;
    }

    public function setMax(mixed $max): void
    {
        $this->max = $max;
    }

    public function getMin(): mixed
    {
        return $this->min;
    }

    public function setMin(mixed $min): void
    {
        $this->min = $min;
    }

    public function getLast(): mixed
    {
        return $this->last;
    }

    public function setLast(mixed $last): void
    {
        $this->last = $last;
    }

    public function getFirst(): mixed
    {
        return $this->first;
    }

    public function setFirst(mixed $first): void
    {
        $this->first = $first;
    }

    public function getAverage(): mixed
    {
        return $this->average;
    }

    public function setAverage(mixed $average): void
    {
        $this->average = $average;
    }

    public function getSum(): mixed
    {
        return $this->sum;
    }

    public function setSum(mixed $sum): void
    {
        $this->sum = $sum;
    }

    public function getCount(): mixed
    {
        return $this->count;
    }

    public function setCount(mixed $count): void
    {
        $this->count = $count;
    }

    public function getTo(): ?DateTime
    {
        return $this->to;
    }

    public function setTo(?DateTime $to): void
    {
        $this->to = $to;
    }

    public function getFrom(): ?DateTime
    {
        return $this->from;
    }

    public function setFrom(?DateTime $from): void
    {
        $this->from = $from;
    }
}
