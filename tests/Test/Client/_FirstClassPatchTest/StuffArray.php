<?php

namespace tests\RavenDB\Test\Client\_FirstClassPatchTest;

use RavenDB\Type\TypedArray;

class StuffArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(Stuff::class);
    }
}
