<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Type\TypedList;

class CounterDetailList extends TypedList
{
    public function __construct()
    {
        parent::__construct(CounterDetail::class);
        $this->setNullAllowed(true);
    }
}
