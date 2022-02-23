<?php

namespace RavenDB\Documents\Operations;

use InvalidArgumentException;
use RavenDB\Type\TypedArrayInterface;

abstract class ResultResponse
{
    private string $className;
    private TypedArrayInterface $results;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function getResults(): TypedArrayInterface
    {
        return $this->results;
    }

    public function setResults(TypedArrayInterface $results): void
    {
        if ((!$results instanceof $this->className)) {
            throw new InvalidArgumentException('Results must be instance of: ' . $this->className);
        }

        $this->results = $results;
    }
}
