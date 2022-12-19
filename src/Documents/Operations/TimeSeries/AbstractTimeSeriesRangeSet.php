<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedSet;

// !status = DONE
class AbstractTimeSeriesRangeSet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(AbstractTimeSeriesRange::class);
    }
}
