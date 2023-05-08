<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Session\Operations\Lazy\LazyClusterTransactionOperations;
use RavenDB\Type\StringArray;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValue;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueMap;

class ClusterTransactionOperations extends ClusterTransactionOperationsBase
    implements ClusterTransactionOperationsInterface
{

    public function __construct(?DocumentSession $session)
    {
        parent::__construct($session);
    }

    public function lazily(): LazyClusterTransactionOperationsInterface
    {
        return new LazyClusterTransactionOperations($this->session);
    }

    public function getCompareExchangeValue(?string $className, ?string $key): ?CompareExchangeValue
    {
        return $this->getCompareExchangeValueInternal($className, $key);
    }

    /**
     * @param string                   $className
     * @param array|string|StringArray $keysOrStartsWith
     * @param int                      $start
     * @param int                      $pageSize
     *
     * @return CompareExchangeValueMap
     */
    public function getCompareExchangeValues(string $className, array|string|StringArray $keysOrStartsWith, int $start = 0, int $pageSize = 25): CompareExchangeValueMap
    {
        if (!is_string($keysOrStartsWith)) {
            return $this->getCompareExchangeValuesByKeys($className, $keysOrStartsWith);
        }

        return $this->getCompareExchangeValuesByPagination($className, $keysOrStartsWith, $start, $pageSize);
    }

    /**
     * @param string            $className
     * @param StringArray|array $keys
     *
     * @return CompareExchangeValueMap
     */
    protected function getCompareExchangeValuesByKeys(string $className, $keys): CompareExchangeValueMap
    {
        if (is_array($keys)) {
            $keys = StringArray::fromArray($keys);
        }

        return parent::getCompareExchangeValuesInternalByKeys($className, $keys);
    }

    protected function getCompareExchangeValuesByPagination(string $className, ?string $startsWith, int $start, int $pageSize): CompareExchangeValueMap
    {
        return parent::getCompareExchangeValuesInternalByPagination($className, $startsWith, $start, $pageSize);
    }
}
