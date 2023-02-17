<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedList;

class FacetList extends TypedList
{
    public function __construct()
    {
        parent::__construct(Facet::class);
    }
}
