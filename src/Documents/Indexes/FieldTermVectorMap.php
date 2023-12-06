<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedMap;

class FieldTermVectorMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(FieldTermVector::class);
    }
}
