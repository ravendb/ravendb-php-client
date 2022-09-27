<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedList;

class FacetList extends TypedList
{
    public function __construct()
    {
        parent::__construct(Facet::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): FacetList
    {
        $sa = new FacetList();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
