<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\TypedArray;

class RevisionIncludesTokenArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(RevisionIncludesToken::class);
    }
}
