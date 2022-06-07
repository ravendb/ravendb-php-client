<?php

namespace tests\RavenDB\Test\Issues\RavenDB_11649Test;

use RavenDB\Type\TypedArray;

class InnerClassMatrix extends TypedArray
{
    public function __construct()
    {
        parent::__construct(InnerClassArray::class);
    }
}
