<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\TypedArray;

class ExplainQueryResultArray extends TypedArray implements ResultInterface
{
    public function __construct()
    {
        parent::__construct(ExplainQueryResult::class);
    }
}
