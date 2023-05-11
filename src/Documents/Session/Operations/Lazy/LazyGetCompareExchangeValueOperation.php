<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueMap;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueResultParser;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\ClusterTransactionOperationsBase;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\NotImplementedException;
use RavenDB\Exceptions\RavenException;
use RavenDB\Utils\UrlUtils;
use Throwable;

class LazyGetCompareExchangeValueOperation implements LazyOperationInterface
{
    private ?ClusterTransactionOperationsBase $clusterSession = null;
    private ?string $className = null;
    private ?DocumentConventions $conventions = null;
    private ?string $key = null;

    private mixed $result = null;
    private bool $requiresRetry = false;

    public function __construct(?ClusterTransactionOperationsBase $clusterSession,
                                                $className, ?DocumentConventions $conventions, ?string $key) {
        if ($clusterSession == null) {
            throw new IllegalArgumentException("Cluster Session cannot be null");
        }
        if ($className == null) {
            throw new IllegalArgumentException("Clazz cannot be null");
        }
        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }
        if ($key == null) {
            throw new IllegalArgumentException("Key cannot be null");
        }

        $this->clusterSession = $clusterSession;
        $this->className = $className;
        $this->conventions = $conventions;
        $this->key = $key;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getQueryResult(): ?QueryResult
    {
        throw new NotImplementedException("Not implemented");
    }

    public function isRequiresRetry(): bool
    {
        return $this->requiresRetry;
    }

    public function createRequest(): ?GetRequest
    {
        if ($this->clusterSession->isTracked($this->key)) {
            $reference = null;
            $this->result  = $this->clusterSession->getCompareExchangeValueFromSessionInternal($this->className, $this->key, $reference);
            return null;
        }

        $request = new GetRequest();
        $request->setUrl("/cmpxchg");
        $request->setMethod("GET");
        $queryBuilder = "?key=" . UrlUtils::escapeDataString($this->key);
        $request->setQuery($queryBuilder);

        return $request;
    }

    public function handleResponse(?GetResponse $response): void
    {
        if ($response->isForceRetry()) {
            $this->result = null;
            $this->requiresRetry = true;
            return;
        }

        try {
            if ($response->getResult() != null) {

                $value = CompareExchangeValueResultParser::getValue(null, $response->getResult(), false, $this->conventions);

                if ($this->clusterSession->getSession()->noTracking) {
                    if ($value == null) {
                        $this->result = $this->clusterSession->registerMissingCompareExchangeValue($this->key)->getValue($this->className, $this->conventions);
                        return;
                    }

                    $this->result = $this->clusterSession->registerCompareExchangeValue($value)->getValue($this->className, $this->conventions);
                    return;
                }

                if ($value != null) {
                    $this->clusterSession->registerCompareExchangeValue($value);
                }
            }

            if (!$this->clusterSession->isTracked($this->key)) {
                $this->clusterSession->registerMissingCompareExchangeValue($this->key);
            }

            $notTrackedRef = false;
            $this->result = $this->clusterSession->getCompareExchangeValueFromSessionInternal($this->className, $this->key, $notTrackedRef);
        } catch (Throwable $e) {
            throw new RavenException("Unable to get compare exchange value: " . $this->key, $e);
        }
    }
}
