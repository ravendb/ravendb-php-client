<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\TypedMap;

// !status: DONE
class SpatialOptionsMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(SpatialOptions::class);
    }
}
