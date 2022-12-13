<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedList;

class TimeSeriesRangeResultList extends TypedList
{
    public function __construct()
    {
        parent::__construct(TimeSeriesRangeResult::class);
    }
}
