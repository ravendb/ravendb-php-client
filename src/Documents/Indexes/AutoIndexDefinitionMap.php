<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedMap;

class AutoIndexDefinitionMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(AutoIndexDefinition::class);
    }
}
