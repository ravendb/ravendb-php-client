<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

// !status: DONE
class AbstractIndexCreationTaskArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(AbstractIndexCreationTask::class);
    }
}
