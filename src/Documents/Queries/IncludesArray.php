<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\TypedArray;

class IncludesArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(Includes::class);
    }
}
