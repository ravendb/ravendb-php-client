<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedArray;

// !status = DONE
class AbstractTimeSeriesRangeArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(AbstractTimeSeriesRange::class);
    }
}
