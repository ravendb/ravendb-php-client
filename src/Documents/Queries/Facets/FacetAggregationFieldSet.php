<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedSet;

class FacetAggregationFieldSet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(FacetAggregationField::class);
    }
}
