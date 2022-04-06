<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedArray;

class ItemFlagsSet extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ItemFlags::class);
    }
}
