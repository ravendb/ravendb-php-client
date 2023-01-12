<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedList;

class TimeSeriesRangeList extends TypedList
{
    public function __construct()
    {
        parent::__construct(TimeSeriesRange::class);
    }
}
