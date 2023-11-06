<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedMap;

class FieldIndexingMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(FieldIndexing::class);
    }
}
