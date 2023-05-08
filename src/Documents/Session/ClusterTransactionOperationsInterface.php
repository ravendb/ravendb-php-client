<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\StringArray;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValue;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueMap;

interface ClusterTransactionOperationsInterface extends ClusterTransactionOperationsBaseInterface
{
    function getCompareExchangeValue(?string $className, ?string $key): ?CompareExchangeValue;

    /**
     * @param string                   $className
     * @param array|string|StringArray $keysOrStartsWith
     * @param int                      $start
     * @param int                      $pageSize
     *
     * @return CompareExchangeValueMap
     */
    public function getCompareExchangeValues(string $className, array|string|StringArray $keysOrStartsWith, int $start = 0, int $pageSize = 25): CompareExchangeValueMap;

    function lazily(): LazyClusterTransactionOperationsInterface;
}
