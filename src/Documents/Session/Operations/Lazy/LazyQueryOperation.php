<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\Operations\QueryOperation;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventHelper;
use RavenDB\Type\Duration;
use RavenDB\Type\ObjectArray;
use RuntimeException;
use Throwable;

class LazyQueryOperation implements LazyOperationInterface
{
    private string $className;
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?QueryOperation $queryOperation = null;
    private ?ClosureArray $afterQueryExecuted = null;

    public function __construct(?string $className, ?InMemoryDocumentSessionOperations $session, ?QueryOperation $queryOperation, ClosureArray $afterQueryExecuted)
    {
        $this->className = $className;
        $this->session = $session;
        $this->queryOperation = $queryOperation;
        $this->afterQueryExecuted = $afterQueryExecuted;
    }

    public function createRequest(): GetRequest
    {
        $request = new GetRequest();
        $request->setCanCacheAggressively(!$this->queryOperation->getIndexQuery()->isDisableCaching() && !$this->queryOperation->getIndexQuery()->isWaitForNonStaleResults());
        $request->setUrl("/queries");
        $request->setMethod("POST");
        $request->setQuery("?queryHash=" . $this->queryOperation->getIndexQuery()->getQueryHash($this->session->getConventions()->getEntityMapper()));
        $request->setContent(new IndexQueryContent($this->session->getConventions(), $this->queryOperation->getIndexQuery()));
        return $request;
    }

    private ?ObjectArray $result = null;
    private ?QueryResult $queryResult = null;
    private bool $requiresRetry = false;

    public function getResult(): ?ObjectArray
    {
        return $this->result;
    }

    public function setResult(?ObjectArray $result): void
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


    public function handleResponse(GetResponse $response): void
    {
        if ($response->isForceRetry()) {
            $this->result = null;
            $this->requiresRetry = true;
            return;
        }

        $queryResult = null;

        if ($response->getResult() != null) {
            try {
                $queryResult = JsonExtensions::getDefaultMapper()->denormalize($response->getResult(), QueryResult::class);
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        }

        $this->_handleResponse($queryResult, $response->getElapsed());
    }

    private function _handleResponse(?QueryResult $queryResult, Duration $duration)
    {
        $this->queryOperation->ensureIsAcceptableAndSaveResult($queryResult, $duration);

        EventHelper::invoke($this->afterQueryExecuted, $queryResult);
        $this->result = ObjectArray::fromArray($this->queryOperation->complete($this->className));
        $this->queryResult = $queryResult;
    }
}
