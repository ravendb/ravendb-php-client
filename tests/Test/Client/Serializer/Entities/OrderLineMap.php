<?php

namespace tests\RavenDB\Test\Client\Serializer\Entities;

use RavenDB\Type\TypedMap;

class OrderLineMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(OrderLine::class);
    }
}
