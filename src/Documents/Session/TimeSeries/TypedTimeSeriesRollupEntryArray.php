<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Type\TypedArray;

class TypedTimeSeriesRollupEntryArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TypedTimeSeriesRollupEntry::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): TypedTimeSeriesRollupEntryArray
    {
        $sa = new TypedTimeSeriesRollupEntryArray();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
