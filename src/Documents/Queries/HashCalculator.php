<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Extensions\EntityMapper;

class HashCalculator
{
    private array $buffer = [];

    public function write($value, ?EntityMapper $mapper = null): void
    {
        $serializedValue = $mapper != null ? $mapper->serialize($value, 'json') : serialize($value);
        $this->buffer[] = $serializedValue;
    }

    public function getHash(): string
    {
        return md5(implode($this->buffer));
    }
}
