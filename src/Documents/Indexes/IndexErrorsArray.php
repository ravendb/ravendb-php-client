<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\TypedArray;

class IndexErrorsArray extends TypedArray implements ResultInterface
{
    public function __construct()
    {
        parent::__construct(IndexErrors::class);
    }
}
