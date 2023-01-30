<?php

namespace RavenDB\Documents\Queries\Sorting;

use RavenDB\Type\TypedArray;

class SorterDefinitionArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(SorterDefinition::class);
    }
}
