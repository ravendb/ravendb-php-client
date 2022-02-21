<?php

namespace RavenDB\Utils;

class Logger
{
    private string $className = '';

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function isInfoEnabled(): bool
    {
        return true;
    }

    public function info(string $info): void
    {
        // @todo: implement this method
    }
}
