<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\TypedArray;

// !status: DONE
class ScriptResolverArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ScriptResolver::class);
    }
}
