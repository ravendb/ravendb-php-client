<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedMap;

class ForceRevisionStrategyArray extends TypedMap
{
    public function __construct()
    {
        parent::__construct(ForceRevisionStrategy::class);
        $this->setKeysCaseInsensitive(true);
    }
}
