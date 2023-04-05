<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialTest;

use RavenDB\Type\TypedArray;

class MyDocumentItemList extends TypedArray
{
    public function __construct()
    {
        parent::__construct(MyDocumentItem::class);
    }
}
