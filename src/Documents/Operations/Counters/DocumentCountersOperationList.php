<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Type\TypedList;

class DocumentCountersOperationList extends TypedList
{
    public function __construct()
    {
        parent::__construct(DocumentCountersOperation::class);
    }
}
