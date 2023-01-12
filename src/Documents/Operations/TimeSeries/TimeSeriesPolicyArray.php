<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedArray;

// !status: DONE
class TimeSeriesPolicyArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesPolicy::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): TimeSeriesPolicyArray
    {
        $array = new TimeSeriesPolicyArray();
        $array->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $array->offsetSet($key, $value);
        }

        return $array;
    }
}
