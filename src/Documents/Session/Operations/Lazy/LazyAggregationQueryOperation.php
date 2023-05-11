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

class LazyAggregationQueryOperation implements LazyOperationInterface
{
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?IndexQuery $indexQuery = null;
    private ?Closure $invokeAfterQueryExecuted = null;
    private ?Closure $processResults = null;

    public function __construct(
        ?InMemoryDocumentSessionOperations $session,
        ?IndexQuery                        $indexQuery,
        Closure                            $invokeAfterQueryExecuted,
        Closure                            $processResults)
    {
        $this->session = $session;
        $this->indexQuery = $indexQuery;
        $this->invokeAfterQueryExecuted = $invokeAfterQueryExecuted;
        $this->processResults = $processResults;
    }

    public function createRequest(): ?GetRequest
    {
        $request = new GetRequest();
        $request->setUrl("/queries");
        $request->setMethod("POST");
        $request->setQuery("?queryHash=" . $this->indexQuery->getQueryHash($this->session->getConventions()->getEntityMapper()));
        $request->setContent(new IndexQueryContent($this->session->getConventions(), $this->indexQuery));
        return $request;
    }

    private mixed $result = null;
    private ?QueryResult $queryResult = null;
    private bool $requiresRetry = false;

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function setResult(mixed $result)
    {
        $this->result = $result;
    }

    public function getQueryResult(): ?QueryResult
    {
        return $this->queryResult;
    }

    public function setQueryResult(?QueryResult $queryResult): void
    {
        $this->queryResult = $queryResult;
    }


    public function isRequiresRetry(): bool
    {
        return $this->requiresRetry;
    }

    public function setRequiresRetry(bool $requiresRetry): void
    {
        $this->requiresRetry = $requiresRetry;
    }

    public function handleResponse(?GetResponse $response): void
    {
        if ($response->isForceRetry()) {
            $this->result = null;
            $this->requiresRetry = true;
            return;
        }

        try {
            /** @var QueryResult  $queryResult */
            $queryResult = JsonExtensions::getDefaultMapper()->denormalize($response->getResult(), QueryResult::class);
            $this->_handleResponse($queryResult);
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    private function _handleResponse(?QueryResult $queryResult): void
    {
        $processResults = $this->processResults;
        $this->result = $processResults($queryResult);
        $this->queryResult = $queryResult;
    }
}
