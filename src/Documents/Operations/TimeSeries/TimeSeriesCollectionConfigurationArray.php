<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesCollectionConfigurationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesCollectionConfiguration::class);
    }
}
