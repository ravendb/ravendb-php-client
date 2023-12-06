<?php

namespace RavenDB\Documents\Operations\Identities;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetIdentitiesCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(null);
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/debug/identities";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        $this->result = json_decode($response, true);
    }
}
