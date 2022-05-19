<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

// !status: DONE
class IndexStatusArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(IndexStatus::class);
    }
}
