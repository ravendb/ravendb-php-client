<?php

namespace RavenDB\Http;

use Closure;

// Wrapper class that will hold the logic for async calls implementation if we ever implement in php
class CompletableFuture
{
    private function __construct()
    {

    }

    public static function create(): CompletableFuture
    {
        return new CompletableFuture();
    }

    public static function allOf(CompletableFuture ...$tasks): CompletableFuture
    {
        // currently, does nothing, maybe one day we will implement this
        return CompletableFuture::create();
    }

    public function add(callable $handle, mixed ...$parameters)
    {
        $handle(...$parameters);
    }

    public function wait(): void
    {

    }
}
