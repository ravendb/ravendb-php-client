<?php

namespace RavenDB\ServerWide\Commands;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Http\Topology;
use RavenDB\Type\Duration;
use RavenDB\Utils\UrlUtils;

class GetDatabaseTopologyCommand extends RavenCommand
{
    private ?string $applicationIdentifier = null;
    private ?string $debugTag = null;

    public function __construct(?string $debugTag = null, ?string $applicationIdentifier = null)
    {
        parent::__construct(Topology::class);
        $this->debugTag = $debugTag;
        $this->applicationIdentifier = $applicationIdentifier;

        $this->timeout = Duration::ofSeconds(15);
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/topology?name=" . $serverNode->getDatabase();

        if ($this->debugTag != null) {
            $url .= "&" . $this->debugTag;
        }

        if ($this->applicationIdentifier != null) {
            $url .= "&applicationIdentifier=" . urlEncode($this->applicationIdentifier);
        }

        if (strpos(strtolower($serverNode->getUrl()),".fiddler") !== false) {
            // we want to keep the '.fiddler' stuff there so we'll keep tracking request
            // so we are going to ask the server to respect it
            $url .= "&localUrl=" . UrlUtils::escapeDataString($serverNode->getUrl());
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
