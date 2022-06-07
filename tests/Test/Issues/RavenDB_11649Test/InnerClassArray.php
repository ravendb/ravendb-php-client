<?php

namespace tests\RavenDB\Test\Issues\RavenDB_11649Test;

use RavenDB\Type\TypedArray;

class InnerClassArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(InnerClass::class);
    }
}
