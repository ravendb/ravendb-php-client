<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class HeartRateMeasureWithCustomName
{
    #[TimeSeriesValue(0, "HR")]
    private ?float $heartRate;

    public function getHeartRate(): ?float
    {
        return $this->heartRate;
    }

    public function setHeartRate(?float $heartRate): void
    {
        $this->heartRate = $heartRate;
    }
}
