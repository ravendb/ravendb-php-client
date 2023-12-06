<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\TypedArray;

class IndexStatsArray extends TypedArray implements ResultInterface
{
    public function __construct()
    {
        parent::__construct(IndexStats::class);
    }
}
