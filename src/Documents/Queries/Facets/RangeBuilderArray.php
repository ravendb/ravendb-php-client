<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\TypedArray;

class RangeBuilderArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(RangeBuilder::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): RangeBuilderArray
    {
        $sa = new RangeBuilderArray();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
