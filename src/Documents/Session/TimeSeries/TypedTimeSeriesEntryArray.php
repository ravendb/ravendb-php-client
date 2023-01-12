<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Type\TypedArray;

class TypedTimeSeriesEntryArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TypedTimeSeriesEntry::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): TypedTimeSeriesEntryArray
    {
        $sa = new TypedTimeSeriesEntryArray();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
