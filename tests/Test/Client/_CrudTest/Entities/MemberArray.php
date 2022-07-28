<?php

namespace tests\RavenDB\Test\Client\_CrudTest\Entities;

use RavenDB\Type\TypedArray;

class MemberArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(Member::class);
    }
}
