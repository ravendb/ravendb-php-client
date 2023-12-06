<?php

namespace RavenDB\Documents\Operations\TimeSeries;

class TimeSeriesCountRange extends AbstractTimeSeriesRange
{
    private int $count = 0;
    private TimeSeriesRangeType $type;

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getType(): TimeSeriesRangeType
    {
        return $this->type;
    }

    public function setType(TimeSeriesRangeType $type): void
    {
        $this->type = $type;
    }
}
