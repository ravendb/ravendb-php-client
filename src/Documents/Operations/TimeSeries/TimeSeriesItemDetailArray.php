<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesItemDetailArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesItemDetail::class);
    }
}
