<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class HeartRateMeasure
{
    #[TimeSeriesValue(0)]
    private ?float $heartRate;

    public function getHeartRate(): ?float
    {
        return $this->heartRate;
    }

    public function setHeartRate(?float $heartRate): void
    {
        $this->heartRate = $heartRate;
    }

    public static function create(float $value): HeartRateMeasure
    {
        $measure = new HeartRateMeasure();
        $measure->setHeartRate($value);
        return $measure;
    }
}
