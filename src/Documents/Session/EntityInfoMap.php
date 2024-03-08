<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedMap;

class EntityInfoMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(EntityInfo::class);
        $this->useKeysCaseInsensitive();
    }
}
