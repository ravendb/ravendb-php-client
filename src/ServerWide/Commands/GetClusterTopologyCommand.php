<?php

namespace RavenDB\ServerWide\Commands;

use RavenDB\Http\ClusterTopologyResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetClusterTopologyCommand extends RavenCommand
{
    private ?string $debugTag;

    public function __construct(?string $debugTag = null)
    {
        parent::__construct(ClusterTopologyResponse::class);
        $this->debugTag = $debugTag;
    }

    public function getDebugTag(): ?string
    {
        return $this->debugTag;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . '/cluster/topology';

        if ($this->debugTag != null) {
            $url .= '?' . $this->debugTag;
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }


    public function isReadRequest(): bool
    {
        return true;
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }
        $this->setResult($this->getMapper()->deserialize($response, $this->getResultClass(), 'json'));
    }
}
