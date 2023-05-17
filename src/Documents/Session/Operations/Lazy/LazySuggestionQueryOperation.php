<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use Closure;
use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Extensions\JsonExtensions;
use RuntimeException;
use Throwable;

class LazySuggestionQueryOperation
{
    private ?array $result = null;
    private ?QueryResult $queryResult = null;
    private bool $requiresRetry = false;

    private ?InMemoryDocumentSessionOperations $session = null;
    private ?IndexQuery $indexQuery = null;
    private ?Closure $invokeAfterQueryExecuted = null;
    private ?Closure $processResults = null;

    public function __construct(
        ?InMemoryDocumentSessionOperations $session,
        ?IndexQuery $indexQuery,
        Closure $invokeAfterQueryExecuted,
        Closure $processResults
    ) {
        $this->session = $session;
        $this->indexQuery = $indexQuery;
        $this->invokeAfterQueryExecuted = $invokeAfterQueryExecuted;
        $this->processResults = $processResults;
    }

    public function createRequest(): GetRequest
    {
        $request = new GetRequest();
        $request->setCanCacheAggressively(!$this->indexQuery->isDisableCaching() && !$this->indexQuery->isWaitForNonStaleResults());
        $request->setUrl("/queries");
        $request->setMethod("POST");
        $request->setQuery("?queryHash=" . $this->indexQuery->getQueryHash($this->session->getConventions()->getEntityMapper()));
        $request->setContent(new IndexQueryContent($this->session->getConventions(), $this->indexQuery));

        return $request;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function getQueryResult(): QueryResult
    {
        return $this->queryResult;
    }


    public function isRequiresRetry(): bool
    {
        return $this->requiresRetry;
    }

    public function handleResponse(GetResponse $response): void
    {
        if ($response->isForceRetry()) {
            $this->result = null;
            $this->requiresRetry = true;
            return;
        }

        try {
            $queryResult = JsonExtensions::getDefaultMapper()->denormalize($response->getResult(), QueryResult::class);

            $this->_handleResponse($queryResult);
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    private function _handleResponse(?QueryResult $queryResult): void
    {
        $f = $this->processResults;
        $this->result = $f($queryResult);
        $this->queryResult = $queryResult;
    }
}
