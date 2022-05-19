<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class ExplainQueryCommand extends RavenCommand
{
    private ?DocumentConventions $conventions = null;
    private ?IndexQuery $indexQuery = null;

    public function __construct(?DocumentConventions $conventions, ?IndexQuery $indexQuery)
    {
        parent::__construct(ExplainQueryResultArray::class);
        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }

        if ($indexQuery == null) {
            throw new IllegalArgumentException("IndexQuery cannot be null");
        }

        $this->conventions = $conventions;
        $this->indexQuery = $indexQuery;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" .
                $serverNode->getDatabase() .
                "/queries?debug=explain";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => JsonExtensions::writeIndexQuery($this->conventions, $this->indexQuery),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->result = null;
            return;
        }

        $decodedResponse = $this->getMapper()->deserialize($response, ExplainQueryResponse::class, 'json');
        $this->result = $decodedResponse->getResults();
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
