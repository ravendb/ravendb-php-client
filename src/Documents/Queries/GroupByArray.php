<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\TypedArray;

class GroupByArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(GroupBy::class);
    }
}
