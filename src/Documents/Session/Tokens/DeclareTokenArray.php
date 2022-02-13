<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\TypedArray;

class DeclareTokenArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DeclareToken::class);
    }
}
