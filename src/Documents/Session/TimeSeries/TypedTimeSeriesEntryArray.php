<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Type\TypedArray;

class TypedTimeSeriesEntryArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TypedTimeSeriesEntry::class);
    }
}
