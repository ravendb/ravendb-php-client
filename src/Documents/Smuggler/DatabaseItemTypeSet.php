<?php

namespace RavenDB\Documents\Smuggler;

use RavenDB\Type\TypedSet;

// !status: DONE
class DatabaseItemTypeSet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(DatabaseItemType::class);
    }
}
