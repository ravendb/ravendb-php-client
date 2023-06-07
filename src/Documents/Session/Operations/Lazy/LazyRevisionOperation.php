<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\Operations\GetRevisionOperation;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Json\JsonArrayResult;
use RavenDB\Utils\StringBuilder;
use RuntimeException;
use Throwable;

class LazyRevisionOperation implements LazyOperationInterface
{
    private ?string $className = null;
    private ?GetRevisionOperation $getRevisionOperation = null;
    private ?LazyRevisionOperationMode $mode = null;

    private null|object|array $result = null;
    private ?QueryResult $queryResult = null;
    private bool $requiresRetry = false;

    public function __construct(?string $className, ?GetRevisionOperation $getRevisionOperation, ?LazyRevisionOperationMode $mode = null)
    {
        $this->className = $className;
        $this->getRevisionOperation = $getRevisionOperation;
        $this->mode = $mode;
    }

    public function getResult(): object|array|null
    {
        return $this->result;
    }

    public function setResult(object|array|null $result): void
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

    public function createRequest(): ?GetRequest
    {
        $getRevisionsCommand = $this->getRevisionOperation->getCommand();
        $sb = new StringBuilder("?");
        $getRevisionsCommand->getRequestQueryString($sb);
        $getRequest = new GetRequest();
        $getRequest->setMethod("GET");
        $getRequest->setUrl("/revisions");
        $getRequest->setQuery($sb->__toString());
        return $getRequest;
    }

    public function handleResponse(GetResponse $response): void
    {
        try {
            if ($response->getResult() == null) {
                return;
            }
            $responseAsNode = $response->getResult();
            $jsonArray = $responseAsNode["Results"];

            $jsonArrayResult = new JsonArrayResult();
            $jsonArrayResult->setResults($jsonArray);
            $this->getRevisionOperation->setResult($jsonArrayResult);

            $this->result = match ($this->mode->getValue()) {
                LazyRevisionOperationMode::SINGLE => $this->getRevisionOperation->getRevisionFromResult($this->className),
                LazyRevisionOperationMode::MULTI => $this->getRevisionOperation->getRevisionsFor($this->className),
                LazyRevisionOperationMode::MAP => $this->getRevisionOperation->getRevisions($this->className),
                LazyRevisionOperationMode::LIST_OF_METADATA => $this->getRevisionOperation->getRevisionsMetadataFor(),
                default => throw new IllegalArgumentException("Invalid mode: " . $this->mode->getValue()),
            };
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }
}
