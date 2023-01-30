<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Type\TypedArray;

class TypedTimeSeriesRollupEntryArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TypedTimeSeriesRollupEntry::class);
    }
}
