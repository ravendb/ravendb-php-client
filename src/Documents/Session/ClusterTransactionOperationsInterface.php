<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\StringArray;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValue;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueMap;

//@todo: implement this interfaces
interface ClusterTransactionOperationsInterface extends ClusterTransactionOperationsBaseInterface
{
    function getCompareExchangeValue(?string $className, ?string $key): ?CompareExchangeValue;

    /**
     * @param string                   $className
     * @param string|StringArray|array $keysOrStartsWith
     * @param int                      $start
     * @param int                      $pageSize
     *
     * @return CompareExchangeValueMap
     */
    public function getCompareExchangeValues(string $className, $keysOrStartsWith, int $start = 0, int $pageSize = 25): CompareExchangeValueMap;

//    ILazyClusterTransactionOperations lazily();
}
