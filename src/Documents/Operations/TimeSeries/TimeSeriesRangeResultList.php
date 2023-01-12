<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedList;

class TimeSeriesRangeResultList extends TypedList
{
    public function __construct()
    {
        parent::__construct(TimeSeriesRangeResult::class);
    }

    public static function fromArray(array $data, bool $nullAllowed = false): TimeSeriesRangeResultList
    {
        $sa = new TimeSeriesRangeResultList();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
