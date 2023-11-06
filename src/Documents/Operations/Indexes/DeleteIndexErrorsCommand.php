<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\StringArray;

class DeleteIndexErrorsCommand extends VoidRavenCommand
{
    private ?StringArray $indexNames = null;

    public function __construct(?StringArray $indexNames)
    {
        parent::__construct();
        $this->indexNames = $indexNames;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/indexes/errors";

        if ($this->indexNames != null && count($this->indexNames)) {
            $url .= "?";

            foreach ($this->indexNames as $indexName) {
                $url .= "&name=" . $this->urlEncode($indexName);
            }
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);
    }

    public function isReadRequest(): bool
    {
        return false;
    }
}
