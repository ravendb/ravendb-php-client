<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedList;

class RangeFacetList extends TypedList
{
    public function __construct()
    {
        parent::__construct(RangeFacet::class);
    }
}
