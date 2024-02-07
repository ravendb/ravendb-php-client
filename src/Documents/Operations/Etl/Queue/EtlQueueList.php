<?php

namespace RavenDB\Documents\Operations\Etl\Queue;

use RavenDB\Type\TypedList;

class EtlQueueList extends TypedList
{
    public function __construct()
    {
        parent::__construct(EtlQueue::class);
    }
}
