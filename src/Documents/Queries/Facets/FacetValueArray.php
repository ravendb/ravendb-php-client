<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedArray;

class FacetValueArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(FacetValue::class);
    }
}
