<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

// !status = DONE
class RollingIndexDeploymentArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(RollingIndexDeployment::class);
    }
}
