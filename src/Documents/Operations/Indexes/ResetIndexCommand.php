<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\UrlUtils;

class ResetIndexCommand extends VoidRavenCommand
{
    private ?string $indexName = null;

    public function __construct(?string $indexName = null)
    {
        parent::__construct();

        if ($indexName == null) {
            throw new IllegalArgumentException("Index name cannot be null");
        }
        $this->indexName = $indexName;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/indexes?name=" . UrlUtils::escapeDataString($this->indexName);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::RESET);
    }
}
