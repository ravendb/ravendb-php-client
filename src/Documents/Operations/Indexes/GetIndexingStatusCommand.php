<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexingStatus;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetIndexingStatusCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(IndexingStatus::class);
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/indexes/status";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            self::throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
