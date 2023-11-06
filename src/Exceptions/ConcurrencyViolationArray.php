<?php

namespace RavenDB\Exceptions;

use RavenDB\Type\TypedArray;

class ConcurrencyViolationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ConcurrencyViolation::class);
    }
}
