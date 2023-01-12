<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesCollectionConfigurationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesCollectionConfiguration::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): TimeSeriesCollectionConfigurationArray
    {
        $array = new TimeSeriesCollectionConfigurationArray();
        $array->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $array->offsetSet($key, $value);
        }

        return $array;
    }
}
