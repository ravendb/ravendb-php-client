<?php

namespace RavenDB\Documents\Session;

interface EnumerableQueryInterface
{
    /**
     * Materialize query, executes request and returns with results
     *
     * @return array results as list
     */
    public function toList(): array;

    /**
     * Materialize query, executes request and returns with results
     *
     * @return array results as Array
     */
    public function toArray(): array;
}
