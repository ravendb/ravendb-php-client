<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Type\TypedList;

class IdAndChangeVectorList extends TypedList
{
    public function __construct()
    {
        parent::__construct(IdAndChangeVector::class);
    }
}
