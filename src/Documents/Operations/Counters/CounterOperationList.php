<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Type\TypedList;

class CounterOperationList extends TypedList
{
    public function __construct()
    {
        parent::__construct(CounterOperation::class);
    }
}
