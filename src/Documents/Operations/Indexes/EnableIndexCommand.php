<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Utils\UrlUtils;

class EnableIndexCommand extends VoidRavenCommand
{
    private ?string $indexName = null;
    private bool $clusterWide = false;

    public function __construct(?string $indexName, bool $clusterWide = false)
    {
        parent::__construct();

        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        $this->indexName = $indexName;
        $this->clusterWide = $clusterWide;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl()
            . "/databases/" . $serverNode->getDatabase()
            . "/admin/indexes/enable?name=" . UrlUtils::escapeDataString($this->indexName)
            . "&clusterWide=" . $this->clusterWide;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
