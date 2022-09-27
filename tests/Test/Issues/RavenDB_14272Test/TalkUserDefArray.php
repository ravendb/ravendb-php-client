<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14272Test;

use RavenDB\Type\TypedArray;

class TalkUserDefArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(TalkUserDef::class);
    }
}
