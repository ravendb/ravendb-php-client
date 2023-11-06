<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Primitives\TimeValue;

class TimeSeriesTimeRange extends AbstractTimeSeriesRange
{
    private TimeValue $time;
    private TimeSeriesRangeType $type;

    public function getTime(): TimeValue
    {
        return $this->time;
    }

    public function setTime(TimeValue $time): void
    {
        $this->time = $time;
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
