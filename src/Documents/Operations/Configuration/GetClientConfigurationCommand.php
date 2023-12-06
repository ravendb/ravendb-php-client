<?php

namespace RavenDB\Documents\Operations\Configuration;

use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\HttpRequestInterface;

class GetClientConfigurationCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(GetClientConfigurationResult::class);
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/databases/' . $serverNode->getDatabase() . '/configuration/client';
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }
        $this->setResult($this->getMapper()->deserialize($response, $this->getResultClass(), 'json'));
    }
}
