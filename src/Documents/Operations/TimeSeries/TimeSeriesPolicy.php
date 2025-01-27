<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Primitives\TimeValue;
use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeSeriesPolicy
{
    #[SerializedName("Name")]
    protected string $name;

    #[SerializedName("RetentionTime")]
    protected ?TimeValue $retentionTime = null;

    #[SerializedName("AggregationTime")]
    protected ?TimeValue $aggregationTime = null;

    public function __construct(string $name, ?TimeValue $aggregationTime = null, ?TimeValue $retentionTime = null)
    {
        if ($retentionTime == null) {
            $retentionTime = TimeValue::maxValue();
        }

        if (empty($name)) {
            throw new IllegalArgumentException("Name cannot be null or empty");
        }

        if ($aggregationTime->compareTo(TimeValue::zero()) <= 0) {
            throw new IllegalArgumentException("Aggregation time must be greater than zero");
        }

        if ($retentionTime->compareTo(TimeValue::zero()) <= 0) {
            throw new IllegalArgumentException("Retention time must be greater than zero");
        }

        $this->retentionTime = $retentionTime;
        $this->aggregationTime = $aggregationTime;

        $this->name = $name;
    }

    /**
     * @return string Name of the time series policy, must be unique.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name Name of the time series policy, must be unique.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return TimeValue How long the data of this policy will be retained
     */
    public function getRetentionTime(): TimeValue
    {
        return $this->retentionTime;
    }

    /**
     * @param TimeValue $retentionTime How long the data of this policy will be retained
     */
    public function setRetentionTime(TimeValue $retentionTime): void
    {
        $this->retentionTime = $retentionTime;
    }

    /**
     * @return TimeValue Define the aggregation of this policy
     */
    public function getAggregationTime(): ?TimeValue
    {
        return $this->aggregationTime;
    }

    /**
     * @param TimeValue $aggregationTime Define the aggregation of this policy
     */
    public function setAggregationTime(TimeValue $aggregationTime): void {
        $this->aggregationTime = $aggregationTime;
    }

    public function getTimeSeriesName(string $rawName): string {
        return $rawName . TimeSeriesConfiguration::TIME_SERIES_ROLLUP_SEPARATOR . $this->name;
    }
}
