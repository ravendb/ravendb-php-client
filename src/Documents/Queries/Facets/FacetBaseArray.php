<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedArray;

class FacetBaseArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(FacetBase::class);
    }
}
