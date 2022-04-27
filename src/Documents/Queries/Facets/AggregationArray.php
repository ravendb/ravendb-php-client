<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedArray;

class AggregationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(FacetAggregationFieldSet::class);
    }

    public function set(FacetAggregation $key, FacetAggregationFieldSet $value): void
    {
        parent::offsetSet($key->getValue(), $value);
    }

    public function get(FacetAggregation $key): FacetAggregationFieldSet
    {
        return parent::offsetGet($key->getValue());
    }
}
