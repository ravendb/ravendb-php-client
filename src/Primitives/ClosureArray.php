<?php

namespace RavenDB\Primitives;

use Closure;
use RavenDB\Type\TypedArray;

class ClosureArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(Closure::class);
    }
}
