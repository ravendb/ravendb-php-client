<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Type\TypedList;

class LazyOperationList extends TypedList
{
    public function __construct()
    {
        parent::__construct(LazyOperationInterface::class);
    }
}
