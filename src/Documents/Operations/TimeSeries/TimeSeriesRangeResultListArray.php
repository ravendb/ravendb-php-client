<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesRangeResultListArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesRangeResultList::class);
    }
}
