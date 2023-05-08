<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueResultParser;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\ClusterTransactionOperationsBase;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\NotImplementedException;
use RavenDB\Exceptions\RavenException;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlUtils;
use Throwable;

class LazyGetCompareExchangeValuesOperation implements LazyOperationInterface
{
    private ?ClusterTransactionOperationsBase $clusterSession = null;
    private ?string $className = null;
    private ?DocumentConventions $conventions = null;
    private ?string $startsWith = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?StringArray $keys = null;
    private mixed $result = null;
    private bool $requiresRetry = false;

    public function __construct(
        ?ClusterTransactionOperationsBase $clusterSession,
        ?string                           $className,
        ?DocumentConventions              $conventions,
        null|array|StringArray            $keys)
    {
        if ($keys == null || count($keys) == 0) {
            throw new IllegalArgumentException("Keys cannot be null or empty");
        }

        if (is_array($keys)) {
            $keys = StringArray::fromArray($keys);
        }

        if ($clusterSession == null) {
            throw new IllegalArgumentException("ClusterSession cannot be null");
        }

        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }

        $this->clusterSession = $clusterSession;
        $this->className = $className;
        $this->conventions = $conventions;
        $this->keys = $keys;
        $this->start = 0;
        $this->pageSize = 0;
        $this->startsWith = null;
    }

//    public LazyGetCompareExchangeValuesOperation(ClusterTransactionOperationsBase clusterSession,
//                                                 Class<T> clazz, DocumentConventions conventions, String startsWith,
//                                                 int start, int pageSize) {
//        if (clusterSession == null) {
//            throw new IllegalArgumentException("ClusterSession cannot be null");
//        }
//
//        if (conventions == null) {
//            throw new IllegalArgumentException("Conventions cannot be null");
//        }
//
//        this._clazz = clazz;
//        this._clusterSession = clusterSession;
//        this._conventions = conventions;
//        this._startsWith = startsWith;
//        this._start = start;
//        this._pageSize = pageSize;
//
//        this._keys = null;
//    }

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
        $pathBuilder = null;

        if ($this->keys != null) {
            foreach ($this->keys as $key) {
                if ($this->clusterSession->isTracked($key)) {
                    continue;
                }

                if ($pathBuilder == null) {
                    $pathBuilder = "?";
                }

                $pathBuilder .= "&key=" . UrlUtils::escapeDataString($key);
            }
        } else {
            $pathBuilder = "?";

            if (StringUtils::isNotEmpty($this->startsWith)) {
                $pathBuilder .= "&startsWith=" . UrlUtils::escapeDataString($this->startsWith);
            }

            $pathBuilder .= "&start=" . $this->start;
            $pathBuilder .= "&pageSize=" . $this->pageSize;
        }

        if ($pathBuilder == null) {
            $reference = new StringSet();
            $this->result = $this->clusterSession->getCompareExchangeValuesFromSessionInternal($this->className, $this->keys, $reference);
            return null;
        }

        $request = new GetRequest();
        $request->setUrl("/cmpxchg");
        $request->setMethod("GET");
        $request->setQuery($pathBuilder);

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
                if ($this->clusterSession->getSession()->noTracking) {
                    $result = new ExtendedArrayObject();
                    $result->setKeysCaseInsensitive(true);
                    $values = CompareExchangeValueResultParser::getValues(null, $response->getResult(), false, $this->conventions);
                    foreach ($values as $key => $value) {
                        if ($value == null) {
                            $result[$key] = $this->clusterSession->registerMissingCompareExchangeValue($key)->getValue($this->className, $this->conventions);
                            continue;
                        }

                        $result[$key] = $this->clusterSession->registerCompareExchangeValue($value)->getValue($this->className, $this->conventions);
                    }

                    $this->result = $result;
                    return;
                }

                foreach (CompareExchangeValueResultParser::getValues(null, $response->getResult(), false, $this->conventions) as $key => $value) {
                    if ($value == null) {
                        continue;
                    }

                    $this->clusterSession->registerCompareExchangeValue($value);
                }
            }

            if ($this->keys != null) {
                foreach ($this->keys as $key) {
                    if ($this->clusterSession->isTracked($key)) {
                        continue;
                    }

                    $this->clusterSession->registerMissingCompareExchangeValue($key);
                }
            }

            $reference = new StringSet();
            $this->result = $this->clusterSession->getCompareExchangeValuesFromSessionInternal($this->className, $this->keys, $reference);
        } catch (Throwable $e) {
            throw new RavenException("Unable to get compare exchange values: " . implode(", ", $this->keys->getArrayCopy()));
        } finally {
            usleep(1);
        }
    }
}
