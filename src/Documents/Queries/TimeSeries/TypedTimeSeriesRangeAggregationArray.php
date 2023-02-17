<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use RavenDB\Type\TypedArray;

class TypedTimeSeriesRangeAggregationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TypedTimeSeriesRangeAggregation::class);
    }
}
