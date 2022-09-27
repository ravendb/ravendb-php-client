<?php

namespace tests\RavenDB\Infrastructure\Orders;

use RavenDB\Type\TypedList;

class TerritoryList extends TypedList
{
    public function __construct()
    {
        parent::__construct(Territory::class);
    }
}
