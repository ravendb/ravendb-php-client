<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Type\TypedList;

class TimeSeriesEntryList extends TypedList
{
    public function __construct()
    {
        parent::__construct(TimeSeriesEntry::class);
    }
}
