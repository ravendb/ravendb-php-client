<?php

namespace RavenDB\Documents\Indexes\TimeSeries;

use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexSourceType;

class TimeSeriesIndexDefinition extends IndexDefinition
{
    public function getSourceType(): IndexSourceType
    {
        return IndexSourceType::timeSeries();
    }
}
