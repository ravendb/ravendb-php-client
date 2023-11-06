<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

class CollectionStatsArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(CollectionStats::class);
    }
}
