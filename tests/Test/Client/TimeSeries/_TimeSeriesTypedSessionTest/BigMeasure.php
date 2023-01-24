<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class BigMeasure
{
    #[TimeSeriesValue(0)]
    private ?float $measure1 = null;

    #[TimeSeriesValue(1)]
    private ?float $measure2 = null;

    #[TimeSeriesValue(2)]
    private ?float $measure3 = null;

    #[TimeSeriesValue(3)]
    private ?float $measure4 = null;

    #[TimeSeriesValue(4)]
    private ?float $measure5 = null;

    #[TimeSeriesValue(5)]
    private ?float $measure6 = null;

    public function getMeasure1(): ?float
    {
        return $this->measure1;
    }

    public function setMeasure1(?float $measure1): void
    {
        $this->measure1 = $measure1;
    }

    public function getMeasure2(): ?float
    {
        return $this->measure2;
    }

    public function setMeasure2(?float $measure2): void
    {
        $this->measure2 = $measure2;
    }

    public function getMeasure3(): ?float
    {
        return $this->measure3;
    }

    public function setMeasure3(?float $measure3): void
    {
        $this->measure3 = $measure3;
    }

    public function getMeasure4(): ?float
    {
        return $this->measure4;
    }

    public function setMeasure4(?float $measure4): void
    {
        $this->measure4 = $measure4;
    }

    public function getMeasure5(): ?float
    {
        return $this->measure5;
    }

    public function setMeasure5(?float $measure5): void
    {
        $this->measure5 = $measure5;
    }

    public function getMeasure6(): ?float
    {
        return $this->measure6;
    }

    public function setMeasure6(?float $measure6): void
    {
        $this->measure6 = $measure6;
    }
}
