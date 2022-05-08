<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedMap;

class ResultMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(ResultInterface::class);
    }
}
