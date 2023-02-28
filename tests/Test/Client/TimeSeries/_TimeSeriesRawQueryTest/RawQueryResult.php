<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesRawQueryTest;

use RavenDB\Documents\Queries\TimeSeries\TimeSeriesAggregationResult;

class RawQueryResult
{
    private ?TimeSeriesAggregationResult $heartRate = null;
    private ?TimeSeriesAggregationResult $bloodPressure = null;
    private ?string $name = null;

    public function getHeartRate(): ?TimeSeriesAggregationResult
    {
        return $this->heartRate;
    }

    public function setHeartRate(?TimeSeriesAggregationResult $heartRate): void
    {
        $this->heartRate = $heartRate;
    }

    public function getBloodPressure(): ?TimeSeriesAggregationResult
    {
        return $this->bloodPressure;
    }

    public function setBloodPressure(?TimeSeriesAggregationResult $bloodPressure): void
    {
        $this->bloodPressure = $bloodPressure;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
