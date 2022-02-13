<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedArray;

class FacetResultArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(FacetResult::class);
    }
}
