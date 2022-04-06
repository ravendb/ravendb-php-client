<?php

namespace RavenDB\Http;

abstract class VoidRavenCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(null);
    }

    public function isReadRequest(): bool
    {
        return false;
    }
}
