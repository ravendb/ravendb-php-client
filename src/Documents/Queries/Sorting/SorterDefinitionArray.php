<?php

namespace RavenDB\Documents\Queries\Sorting;

use RavenDB\Type\TypedArray;

class SorterDefinitionArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(SorterDefinition::class);
    }

    public static function fromArray(array $data): SorterDefinitionArray
    {
        $array = new SorterDefinitionArray();

        foreach ($data as $key => $value) {
            $array->offsetSet($key, $value);
        }

        return $array;
    }
}
