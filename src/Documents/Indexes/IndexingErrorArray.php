<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

class IndexingErrorArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(IndexingError::class);
    }
}
