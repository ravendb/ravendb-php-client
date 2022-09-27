<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedList;

class RangeFacetList extends TypedList
{
    public function __construct()
    {
        parent::__construct(RangeFacetList::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): RangeFacetList
    {
        $sa = new RangeFacetList();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
