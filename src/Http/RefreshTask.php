<?php

namespace RavenDB\Http;

use Closure;

// Wrapper class that will hold the logic for async calls implementation if we ever implement in php
class RefreshTask
{
    private function __construct()
    {

    }

    public static function create(): RefreshTask
    {
        return new RefreshTask();
    }

    public function add(Closure $handle)
    {
        $handle();
    }

    public function wait(): void
    {

    }
}
