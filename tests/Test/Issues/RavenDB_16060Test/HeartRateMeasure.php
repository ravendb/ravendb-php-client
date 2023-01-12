<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16060Test;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class HeartRateMeasure
{
    #[TimeSeriesValue(0)]
    private ?float $heartRate = null;

    /**
     * @return float|null
     */
    public function getHeartRate(): ?float
    {
        return $this->heartRate;
    }

    /**
     * @param float|null $heartRate
     */
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
