<?php

namespace RavenDB\Http;

class VoidRavenCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(null);
    }

    protected function createUrl(ServerNode $serverNode): string
    {
        // TODO: Implement createUrl() method.
    }

    public function isReadRequest(): bool
    {
        return false;
    }
}
