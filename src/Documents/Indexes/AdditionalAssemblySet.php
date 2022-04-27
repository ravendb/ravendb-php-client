<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedSet;

class AdditionalAssemblySet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(AdditionalAssembly::class);
    }
}
