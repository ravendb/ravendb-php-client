<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\Operations\LoadOperation;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlUtils;
use RuntimeException;
use Throwable;

class LazyLoadOperation implements LazyOperationInterface
{
    private ?string $className = null;
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?LoadOperation $loadOperation = null;
    private ?array $ids = null;
    private ?array $includes = null;
    private array $alreadyInSession = [];

    public function __construct(?string $className, ?InMemoryDocumentSessionOperations $session, ?LoadOperation $loadOperation)
    {
        $this->className = $className;
        $this->session = $session;
        $this->loadOperation = $loadOperation;
    }

    public function createRequest(): ?GetRequest
    {
        $queryBuilder = "?";

        if ($this->includes != null) {
            foreach ($this->includes as $include) {
                $queryBuilder .= "&include=" . $include;
            }
        }

        $hasItems = false;

        foreach ($this->ids as $id) {
            if ($this->session->isLoadedOrDeleted($id)) {
                $this->alreadyInSession[] = $id;
            } else {
                $hasItems = true;
                $queryBuilder .= "&id=" . UrlUtils::escapeDataString($id);
            }
        }

        if (!$hasItems) {
            // no need to hit the server
            $this->result = $this->loadOperation->getDocuments($this->className);
            return null;
        }

        $getRequest = new GetRequest();

        $getRequest->setUrl("/docs");
        $getRequest->setQuery($queryBuilder);
        return $getRequest;
    }

    public function byId(?string $id): LazyLoadOperation
    {
        if (StringUtils::isBlank($id)) {
            return $this;
        }

        if ($this->ids == null) {
            $this->ids = [ $id ];
        }

        return $this;
    }

    public function byIds(StringArray $ids): LazyLoadOperation
    {
        $this->ids = array_unique(array_filter($ids->getArrayCopy(), function ($x) { return !StringUtils::isBlank($x); }));

        return $this;
    }

    public function withIncludes(StringArray $includes): LazyLoadOperation
    {
        $this->includes = $includes->getArrayCopy();
        return $this;
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

        try {
            $multiLoadResult = $response->getResult() != null ?
                    JsonExtensions::getDefaultMapper()->denormalize($response->getResult(), GetDocumentsResult::class)
                    : null;
            $this->_handleResponse($multiLoadResult);
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    private function _handleResponse(GetDocumentsResult $loadResult): void
    {
        if (!empty($this->alreadyInSession)) {
            // push this to the session
            (new LoadOperation($this->session))
                    ->byIds($this->alreadyInSession)
                    ->getDocuments($this->className);
        }

        $this->loadOperation->setResult($loadResult);

        if (!$this->requiresRetry) {
            $this->result = $this->loadOperation->getDocuments($this->className);
        }
    }
}
