<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedMap;

class RequestExecutorMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(RequestExecutor::class);
    }
}
