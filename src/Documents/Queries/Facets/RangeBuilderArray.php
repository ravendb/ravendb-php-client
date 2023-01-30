<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedArray;

class RangeBuilderArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(RangeBuilder::class);
    }
}
