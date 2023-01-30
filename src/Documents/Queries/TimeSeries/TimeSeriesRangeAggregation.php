<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use DateTime;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesValuesHelper;
use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeSeriesRangeAggregation
{
    #[SerializedName("Count")]
    private ?array $count = null;

    #[SerializedName("Max")]
    private ?array $max = null;

    #[SerializedName("Min")]
    private ?array $min = null;

    #[SerializedName("Last")]
    private ?array $last = null;

    #[SerializedName("First")]
    private ?array $first = null;

    #[SerializedName("Average")]
    private ?array $average = null;

    #[SerializedName("Sum")]
    private ?array $sum = null;

    #[SerializedName("To")]
    private ?DateTime $to = null;

    #[SerializedName("From")]
    private ?DateTime $from = null;

    public function getCount(): ?array
    {
        return $this->count;
    }

    public function setCount(?array $count): void
    {
        $this->count = $count;
    }

    public function getMax(): ?array
    {
        return $this->max;
    }

    public function setMax(?array $max): void
    {
        $this->max = $max;
    }

    public function getMin(): ?array
    {
        return $this->min;
    }

    public function setMin(?array $min): void
    {
        $this->min = $min;
    }

    public function getLast(): ?array
    {
        return $this->last;
    }

    public function setLast(?array $last): void
    {
        $this->last = $last;
    }

    public function getFirst(): ?array
    {
        return $this->first;
    }

    public function setFirst(?array $first): void
    {
        $this->first = $first;
    }

    public function getAverage(): ?array
    {
        return $this->average;
    }

    public function setAverage(?array $average): void
    {
        $this->average = $average;
    }

    public function getSum(): ?array
    {
        return $this->sum;
    }

    public function setSum(?array $sum): void
    {
        $this->sum = $sum;
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

    public function asTypedEntry(string $className): TypedTimeSeriesRangeAggregation
    {
        $typedEntry = new TypedTimeSeriesRangeAggregation();

        $typedEntry->setFrom($this->from);
        $typedEntry->setTo($this->to);
        $typedEntry->setMin($this->min != null ? TimeSeriesValuesHelper::setFields($className, $this->min, false) : null);
        $typedEntry->setMax($this->max != null ? TimeSeriesValuesHelper::setFields($className, $this->max, false) : null);
        $typedEntry->setFirst($this->first != null ? TimeSeriesValuesHelper::setFields($className, $this->first, false) : null);
        $typedEntry->setLast($this->last != null ? TimeSeriesValuesHelper::setFields($className, $this->last, false) : null);
        $typedEntry->setSum($this->sum != null ? TimeSeriesValuesHelper::setFields($className, $this->sum, false) : null);
        $typedEntry->setCount($this->count != null ? TimeSeriesValuesHelper::setFields($className, $this->count, false) : null);
        $typedEntry->setAverage($this->average != null ? TimeSeriesValuesHelper::setFields($className, $this->average, false) : null);

        return $typedEntry;
    }
}
