<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedList;

class AppendOperationList extends TypedList
{
    protected function __construct()
    {
        parent::__construct(AppendOperation::class);
    }
}
