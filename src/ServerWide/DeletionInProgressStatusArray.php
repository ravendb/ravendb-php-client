<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\TypedArray;

class DeletionInProgressStatusArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DeletionInProgressStatus::class);
    }
}
