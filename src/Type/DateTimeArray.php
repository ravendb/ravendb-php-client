<?php

namespace RavenDB\Type;

use DateTimeInterface;

class DateTimeArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DateTimeInterface::class);
    }
}
