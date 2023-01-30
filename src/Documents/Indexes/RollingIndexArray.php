<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

// !status: DONE
class RollingIndexArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(RollingIndex::class);
    }
}
