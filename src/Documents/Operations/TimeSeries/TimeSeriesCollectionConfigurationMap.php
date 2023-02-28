<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedMap;

class TimeSeriesCollectionConfigurationMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(TimeSeriesCollectionConfiguration::class);
    }
}
