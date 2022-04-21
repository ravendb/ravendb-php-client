<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedMap;

// !status: DONE
class FieldTermVectorMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(FieldTermVector::class);
    }
}
