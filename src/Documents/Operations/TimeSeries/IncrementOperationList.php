<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedList;

class IncrementOperationList extends TypedList
{
    public function __construct()
    {
        parent::__construct(IncrementOperation::class);
    }
}
