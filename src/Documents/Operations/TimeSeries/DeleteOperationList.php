<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\TypedList;

class DeleteOperationList extends TypedList
{
    protected function __construct()
    {
        parent::__construct(DeleteOperation::class);
    }
}
