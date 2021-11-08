<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedMap;

class NodeStatusMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(NodeStatus::class);
    }
}
