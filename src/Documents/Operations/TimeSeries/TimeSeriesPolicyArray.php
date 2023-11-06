<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesPolicyArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesPolicy::class);
    }
}
