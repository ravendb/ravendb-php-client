<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\TypedArray;

class QueryTokenList extends TypedArray
{
    public function __construct()
    {
        parent::__construct(QueryToken::class);
    }
}
