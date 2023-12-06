<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexStats;
use RavenDB\Documents\Operations\GetIndexStatisticsResponse;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\UrlUtils;

class GetIndexStatisticsCommand extends RavenCommand
{
    private ?string $indexName = null;

    public function __construct($indexName)
    {
        parent::__construct(IndexStats::class);

        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        $this->indexName = $indexName;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() .
            "/databases/" . $serverNode->getDatabase() .
            "/indexes/stats?name=" . UrlUtils::escapeDataString($this->indexName);
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

        $results = $this->getMapper()->deserialize($response, GetIndexStatisticsResponse::class, 'json')->getResults();
        if (count($results) != 1) {
            self::throwInvalidResponse();
        }

        $this->result = $results[0];
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
