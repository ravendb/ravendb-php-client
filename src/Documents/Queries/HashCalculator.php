<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Extensions\EntityMapper;
use function Amp\Iterator\concat;

class HashCalculator
{
    private array $buffer = [];

    public function write($value, ?EntityMapper $mapper = null): void
    {
        $serializedValue = $mapper != null ? $mapper->serialize($value, 'json') : serialize($value);

        // @todo: Check with Marcin should we go with first option or second
//        $this->buffer[] = md5($serializedValue);
        $this->buffer[] = $serializedValue;
    }

    public function getHash(): string
    {
        return md5(implode($this->buffer));
    }
}
