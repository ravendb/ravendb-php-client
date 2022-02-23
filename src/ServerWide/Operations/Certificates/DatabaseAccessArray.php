<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Type\TypedArray;

class DatabaseAccessArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DatabaseAccess::class);
    }
}
