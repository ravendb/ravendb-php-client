<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class StockPrice
{
    #[TimeSeriesValue(0)]
    private float $open;
    #[TimeSeriesValue(1)]
    private float $close;
    #[TimeSeriesValue(2)]
    private float $high;
    #[TimeSeriesValue(3)]
    private float $low;
    #[TimeSeriesValue(4)]
    private float $volume;

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
