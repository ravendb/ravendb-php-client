<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\TypedArray;

class LoadTokenList extends TypedArray
{
    public function __construct()
    {
        parent::__construct(LoadToken::class);
    }
}
