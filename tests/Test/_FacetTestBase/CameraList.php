<?php

namespace tests\RavenDB\Test\_FacetTestBase;

use RavenDB\Type\TypedList;

class CameraList extends TypedList
{
    public function __construct()
    {
        parent::__construct(Camera::class);
    }
}
