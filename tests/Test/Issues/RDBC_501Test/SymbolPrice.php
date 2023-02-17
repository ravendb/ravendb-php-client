<?php

namespace tests\RavenDB\Test\Issues\RDBC_501Test;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesValue;

class SymbolPrice
{
    #[TimeSeriesValue(0)]
    public ?float $open;
    #[TimeSeriesValue(1)]
    public ?float $close;
    #[TimeSeriesValue(2)]
    public ?float $high;
    #[TimeSeriesValue(3)]
    public ?float $low;

    public function getOpen(): ?float
    {
        return $this->open;
    }

    public function setOpen(?float $open): void
    {
        $this->open = $open;
    }

    public function getClose(): ?float
    {
        return $this->close;
    }

    public function setClose(?float $close): void
    {
        $this->close = $close;
    }

    public function getHigh(): ?float
    {
        return $this->high;
    }

    public function setHigh(?float $high): void
    {
        $this->high = $high;
    }

    public function getLow(): ?float
    {
        return $this->low;
    }

    public function setLow(?float $low): void
    {
        $this->low = $low;
    }
}
