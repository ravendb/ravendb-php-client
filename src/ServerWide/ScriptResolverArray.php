<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\TypedArray;

class ScriptResolverArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ScriptResolver::class);
    }
}
