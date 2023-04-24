<?php

namespace RavenDB\Documents\Indexes\Counters;

use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexSourceType;

class CountersIndexDefinition extends IndexDefinition
{
    public function getSourceType(): IndexSourceType
    {
        return IndexSourceType::counters();
    }
}
