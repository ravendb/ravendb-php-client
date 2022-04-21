<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedMap;

// !status: DONE
class FieldStorageMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(FieldStorage::class);
    }
}
