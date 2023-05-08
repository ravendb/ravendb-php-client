<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use Closure;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValue;
use RavenDB\Documents\Session\ClusterTransactionOperationsBase;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\LazyClusterTransactionOperationsInterface;
use RavenDB\Type\StringArray;

class LazyClusterTransactionOperations extends ClusterTransactionOperationsBase implements LazyClusterTransactionOperationsInterface
{
    public function __construct(?DocumentSession $session) {
        parent::__construct($session);
    }

    public function getCompareExchangeValue(?string $className, ?string $key, ?Closure $onEval = null): Lazy
    {
        return $this->session->addLazyOperation(
            CompareExchangeValue::class,
                new LazyGetCompareExchangeValueOperation($this, $className, $this->session->getConventions(), $key),
            $onEval
        );
    }

    public function getCompareExchangeValues(?string $className, StringArray|array $keys, ?Closure $onEval = null): Lazy
    {
        if (is_array($keys)) {
            $keys = StringArray::fromArray($keys);
        }
        return $this->session->addLazyOperation(null,
                new LazyGetCompareExchangeValuesOperation($this, $className, $this->session->getConventions(), $keys), $onEval);
    }
}
