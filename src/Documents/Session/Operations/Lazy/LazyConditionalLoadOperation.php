<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Constants\Headers;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Documents\Commands\ConditionalGetResult;
use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\ConditionalLoadResult;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\NotImplementedException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Utils\UrlUtils;
use RuntimeException;
use Throwable;

class LazyConditionalLoadOperation implements LazyOperationInterface
{
    private ?string $className = null;
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?string $id = null;
    private ?string $changeVector = null;

    public function __construct(?string $className, ?string $id, ?string $changeVector, ?InMemoryDocumentSessionOperations $session)
    {
        $this->className = $className;
        $this->id = $id;
        $this->changeVector = $changeVector;
        $this->session = $session;
    }

    public function createRequest(): ?GetRequest
    {
        $request = new GetRequest();

        $request->setUrl("/docs");
        $request->setMethod("GET");
        $request->setQuery("?id=" . UrlUtils::escapeDataString($this->id));

        $headers = $request->getHeaders();
        $headers->offsetSet(Headers::IF_NONE_MATCH, $this->changeVector);
        $request->setHeaders($headers);

        return $request;
    }

    private mixed $result = null;
    private bool $requiresRetry = false;

    public function getQueryResult(): QueryResult
    {
        throw new NotImplementedException();
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function isRequiresRetry(): bool
    {
        return $this->requiresRetry;
    }

    public function setRequiresRetry(bool $requiresRetry): void
    {
        $this->requiresRetry = $requiresRetry;
    }

    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    public function handleResponse(GetResponse $response): void
    {
        if ($response->isForceRetry()) {
            $this->result = null;
            $this->requiresRetry = true;
            return;
        }

        switch ($response->getStatusCode()) {
            case HttpStatusCode::NOT_MODIFIED :
                $this->result = ConditionalLoadResult::create(null, $this->changeVector); // value not changed
                return;
            case HttpStatusCode::NOT_FOUND :
                $this->session->registerMissing($this->id);
                $this->result = ConditionalLoadResult::create(null, null);
                return;
        }

        try {
            if ($response->getResult() != null) {
                $etag = $response->getHeaders()->offsetGet(Headers::ETAG);

                $res = JsonExtensions::getDefaultMapper()->deserialize($response->getResult(), ConditionalGetResult::class, 'json');
                $documentInfo = DocumentInfo::getNewDocumentInfo($res->getResults()[0]);
                $r = $this->session->trackEntity($this->className, $documentInfo);

                $this->result = ConditionalLoadResult::create($r, $etag);
                return;
            }

            $this->result = null;
            $this->session->registerMissing($this->id);
        } catch (Throwable $e) {
            throw new RuntimeException($e);
        }
    }
}
