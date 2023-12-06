<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Type\TypedMap;

class DatabasePromotionStatusMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(DatabasePromotionStatus::class);
    }
}
