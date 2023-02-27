<?php

namespace RavenDB\Documents\Operations\TimeSeries;

class ConfigureRawTimeSeriesPolicyOperation extends ConfigureTimeSeriesPolicyOperation
{
    public function __construct(?string $collection, ?RawTimeSeriesPolicy $config) {
        parent::__construct($collection, $config);
    }
}
