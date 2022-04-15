<?php

namespace RavenDB\Documents\Queries\Timings;

use RavenDB\Type\TypedArray;

class QueryTimingsArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(QueryTimings::class);
    }
}
