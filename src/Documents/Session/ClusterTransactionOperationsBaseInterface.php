<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValue;

interface ClusterTransactionOperationsBaseInterface
{
    /**
     * @template T
     *
     * @param CompareExchangeValue<T>|string|null $keyOrItem
     * @param ?int         $index
     */
    function deleteCompareExchangeValue($keyOrItem, ?int $index = null): void;

    function createCompareExchangeValue(?string $key, $value): CompareExchangeValue;
}
