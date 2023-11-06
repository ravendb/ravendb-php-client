<?php

namespace RavenDB\Documents\Smuggler;

use RavenDB\Type\TypedSet;

class DatabaseItemTypeSet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(DatabaseItemType::class);
    }
}
