<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

class AbstractIndexCreationTaskArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(AbstractIndexCreationTask::class);
    }
}
