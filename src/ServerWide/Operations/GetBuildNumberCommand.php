<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetBuildNumberCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(BuildNumber::class);
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/build/version";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            self::throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }
}
