<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Type\TypedArray;

class TimeSeriesEntryArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TimeSeriesEntry::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): TimeSeriesEntryArray
    {
        $sa = new TimeSeriesEntryArray();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
