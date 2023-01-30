<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class StockPrice
{
    #[TimeSeriesValue(0)]
    private float $open = 0.0;
    #[TimeSeriesValue(1)]
    private float $close = 0.0;
    #[TimeSeriesValue(2)]
    private float $high = 0.0;
    #[TimeSeriesValue(3)]
    private float $low = 0.0;
    #[TimeSeriesValue(4)]
    private float $volume = 0.0;

    public function getOpen(): float
    {
        return $this->open;
    }

    public function setOpen(float $open): void
    {
        $this->open = $open;
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function setClose(float $close): void
    {
        $this->close = $close;
    }

    public function getHigh(): float
    {
        return $this->high;
    }

    public function setHigh(float $high): void
    {
        $this->high = $high;
    }

    public function getLow(): float
    {
        return $this->low;
    }

    public function setLow(float $low): void
    {
        $this->low = $low;
    }

    public function getVolume(): float
    {
        return $this->volume;
    }

    public function setVolume(float $volume): void
    {
        $this->volume = $volume;
    }
}
