<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesEntryArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesEntry::class);
    }
}
