<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15129Test;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class MetricValue
{
    #[TimeSeriesValue(0)]
    private ?float $durationInMs = null;
    #[TimeSeriesValue(1)]
    private ?float $requestSize = null;
    #[TimeSeriesValue(2)]
    private ?string $sourceIp = null;

    public function getDurationInMs(): ?float
    {
        return $this->durationInMs;
    }

    public function setDurationInMs(?float $durationInMs): void
    {
        $this->durationInMs = $durationInMs;
    }

    public function getRequestSize(): ?float
    {
        return $this->requestSize;
    }

    public function setRequestSize(?float $requestSize): void
    {
        $this->requestSize = $requestSize;
    }

    public function getSourceIp(): ?string
    {
        return $this->sourceIp;
    }

    public function setSourceIp(?string $sourceIp): void
    {
        $this->sourceIp = $sourceIp;
    }
}
