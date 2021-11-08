<?php

namespace tests\RavenDB\Test\Client\Serializer\Entities;

use RavenDB\Type\TypedArray;

class OrderLineArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(OrderLine::class);
    }
}
