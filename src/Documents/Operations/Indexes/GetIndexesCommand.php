<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexDefinitionArray;
use RavenDB\Documents\Operations\GetIndexesResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetIndexesCommand extends RavenCommand
{
    private ?int $start = null;
    private ?int $pageSize = null;

    public function __construct(?int $start = null, ?int $pageSize = null)
    {
        parent::__construct(IndexDefinitionArray::class);

        $this->start = $start;
        $this->pageSize = $pageSize;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() .
            "/databases/" . $serverNode->getDatabase() .
            "/indexes?start=" . $this->start .
            "&pageSize=" . $this->pageSize;
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

        $indexesResponse = $this->getMapper()->deserialize($response, GetIndexesResponse::class, 'json');

        $this->result = $indexesResponse->getResults();
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
