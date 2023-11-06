<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\TypedMap;

class SpatialOptionsMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(SpatialOptions::class);
    }
}
