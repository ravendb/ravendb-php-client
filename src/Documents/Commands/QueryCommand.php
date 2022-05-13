<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class QueryCommand extends RavenCommand
{
    private ?InMemoryDocumentSessionOperations $session;
    private IndexQuery $indexQuery;
    private bool $metadataOnly;
    private bool $indexEntriesOnly;

    public function __construct(?InMemoryDocumentSessionOperations $session, ?IndexQuery $indexQuery, bool $metadataOnly, bool $indexEntriesOnly)
    {
        parent::__construct(QueryResult::class);

        $this->session = $session;

        if ($indexQuery == null) {
            throw new IllegalArgumentException("indexQuery cannot be null");
        }

        $this->indexQuery = $indexQuery;
        $this->metadataOnly = $metadataOnly;
        $this->indexEntriesOnly = $indexEntriesOnly;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $path = $serverNode->getUrl()
            . '/databases/'
            . $serverNode->getDatabase()
            . '/queries?queryHash='
            // we need to add a query hash because we are using POST queries
            // so we need to unique parameter per query so the query cache will
            // work properly
            . $this->indexQuery->getQueryHash($this->session->getConventions()->getEntityMapper())
        ;

        if ($this->metadataOnly) {
            $path .= "&metadataOnly=true";
        }

        if ($this->indexEntriesOnly) {
            $path .= "&debug=entries";
        }

        return $path;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $this->canCache = !$this->indexQuery->isDisableCaching();

        // we won't allow aggressive caching of queries with WaitForNonStaleResults
        $this->canCacheAggressively = $this->canCache && !$this->indexQuery->isWaitForNonStaleResults();

        $options = [
            'json' => JsonExtensions::writeIndexQuery($this->session->getConventions(), $this->indexQuery),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if (empty($response)) {
            $result = null;
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, QueryResult::class, 'json');

        if ($fromCache) {
            $this->result->setDurationInMs(-1);

            if ($this->result->getTimings() != null) {
                $this->result->getTimings()->setDurationInMs(-1);
                $this->result->setTimings(null);
            }
        }
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
