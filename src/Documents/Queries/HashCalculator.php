<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Extensions\EntityMapper;

class HashCalculator
{

    public function write($value, ?EntityMapper $mapper = null): void
    {
        // @todo: impement
    }

    public function getHash(): string
    {
        return '1234567890222';
    }
}
