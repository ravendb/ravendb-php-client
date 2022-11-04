<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedMap;

class AutoIndexFieldOptionsMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(AutoIndexFieldOptions::class);
    }
}
