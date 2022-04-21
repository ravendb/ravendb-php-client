<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\TypedArray;

class PutIndexResultArray extends TypedArray implements ResultInterface
{
    public function __construct()
    {
        parent::__construct(PutIndexResult::class);
    }
}
