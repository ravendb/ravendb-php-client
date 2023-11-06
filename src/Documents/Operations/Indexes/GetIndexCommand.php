<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\GetIndexesResponse;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\UrlUtils;

class GetIndexCommand extends RavenCommand
{
    private ?string $indexName = null;

    public function __construct(?string $indexName)
    {
        parent::__construct(IndexDefinition::class);

        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        $this->indexName = $indexName;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/indexes?name=" . UrlUtils::escapeDataString($this->indexName);
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
            return;
        }

        $responseDeserialized = $this->getMapper()->deserialize($response, GetIndexesResponse::class, 'json');

        $this->result = $responseDeserialized->getResults()[0];
    }
}
