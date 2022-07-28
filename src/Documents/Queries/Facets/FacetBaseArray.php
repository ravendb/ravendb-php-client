<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedArray;

class FacetBaseArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(FacetBase::class);
    }

    public static function fromArray(array $data): FacetBaseArray
    {
        $array = new FacetBaseArray();

        foreach ($data as $key => $value) {
            $array->offsetSet($key, $value);
        }

        return $array;
    }
}
