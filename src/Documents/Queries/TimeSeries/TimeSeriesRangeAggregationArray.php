<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesRangeAggregationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesRangeAggregation::class);
    }
}
