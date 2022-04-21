<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

class IndexFieldOptionsArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(IndexFieldOptions::class);
    }
}
