<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Utils\UrlUtils;
use RuntimeException;
use Throwable;

class LazyStartsWithOperation implements LazyOperationInterface
{
    private ?string $className = null;
    private ?string $idPrefix = null;
    private ?string $matches = null;
    private ?string $exclude = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?InMemoryDocumentSessionOperations $sessionOperations = null;
    private ?string $startAfter = null;

    public function __construct(?string $className, ?string $idPrefix, ?string $matches, ?string $exclude, int $start, int $pageSize, ?InMemoryDocumentSessionOperations $sessionOperations, ?string $startAfter)
    {
        $this->className = $className;
        $this->idPrefix = $idPrefix;
        $this->matches = $matches;
        $this->exclude = $exclude;
        $this->start = $start;
        $this->pageSize = $pageSize;
        $this->sessionOperations = $sessionOperations;
        $this->startAfter = $startAfter;
    }

    public function createRequest(): GetRequest
    {
        $request = new GetRequest();
        $request->setUrl("/docs");
        $request->setQuery(sprintf("?startsWith=%s&matches=%s&exclude=%s&start=%d&pageSize=%d&startAfter=%s",
                UrlUtils::escapeDataString($this->idPrefix),
                UrlUtils::escapeDataString($this->matches ?? ""),
                UrlUtils::escapeDataString($this->exclude ?? ""),
                $this->start,
                $this->pageSize,
                $startAfter ?? ""
        ));
        return $request;
    }

    private mixed $result = null;
    private ?QueryResult $queryResult = null;
    private bool $requiresRetry = false;

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function setResult(mixed $result): void
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
        try {
            $getDocumentResult = JsonExtensions::getDefaultMapper()->denormalize($response->getResult(), GetDocumentsResult::class);

            $finalResults = new ExtendedArrayObject();
            $finalResults->setKeysCaseInsensitive(true);

            foreach ($getDocumentResult->getResults() as $document) {
                $newDocumentInfo = DocumentInfo::getNewDocumentInfo($document);
                $this->sessionOperations->documentsById->add($newDocumentInfo);

                if ($newDocumentInfo->getId() == null) {
                    continue; // is this possible?
                }

                if ($this->sessionOperations->isDeleted($newDocumentInfo->getId())) {
                    $finalResults[$newDocumentInfo->getId()] = null;
                    continue;
                }

                $doc = $this->sessionOperations->documentsById->getValue($newDocumentInfo->getId());
                if ($doc != null) {
                    $finalResults[$newDocumentInfo->getId()] = $this->sessionOperations->trackEntity($this->className, $doc);
                    continue;
                }

                $finalResults[$newDocumentInfo->getId()] = null;
            }

            $this->result = $finalResults;
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }
}
