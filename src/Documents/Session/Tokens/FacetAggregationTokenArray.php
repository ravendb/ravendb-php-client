<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\TypedArray;

class FacetAggregationTokenArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(FacetAggregationToken::class);
    }
}
