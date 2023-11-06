<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexErrorsArray;
use RavenDB\Documents\Operations\GetIndexErrorsResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Type\StringArray;

class GetIndexErrorsCommand extends RavenCommand
{
    private ?StringArray $indexNames = null;

    public function __construct(?StringArray $indexNames = null)
    {
        parent::__construct(IndexErrorsArray::class);
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
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            self::throwInvalidResponse();
            return;
        }
        $deserializedResponse = $this->getMapper()->deserialize($response, GetIndexErrorsResponse::class, 'json');

        $this->result = $deserializedResponse->getResults();
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
