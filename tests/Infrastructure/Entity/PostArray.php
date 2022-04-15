<?php

namespace tests\RavenDB\Infrastructure\Entity;

use RavenDB\Type\TypedArray;

// !status: DONE
class PostArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(PostArray::class);
    }
}
