<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetStatisticsCommand extends RavenCommand
{
    private ?string $debugTag = null;

    public function __construct(?string $debugTag = null, ?string $nodeTag = null)
    {
        parent::__construct(DatabaseStatistics::class);
        $this->debugTag = $debugTag;
        $this->selectedNodeTag = $nodeTag;
    }

    public function createUrl(ServerNode $serverNode): string
    {
            $url = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/stats";
            if ($this->debugTag != null) {
                $url .= "?" . $this->debugTag;
            }

            return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache = false): void
    {
        $this->result = $this->getMapper()->deserialize($response, DatabaseStatistics::class, 'json');
    }

    public function isReadRequest(): bool
    {
        return true;
    }


}
