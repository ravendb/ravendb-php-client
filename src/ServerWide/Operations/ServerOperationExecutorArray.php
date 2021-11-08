<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Type\TypedArray;

class ServerOperationExecutorArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ServerOperationExecutor::class);
    }
}
