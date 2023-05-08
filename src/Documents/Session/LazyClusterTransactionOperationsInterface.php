<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Documents\Lazy;
use RavenDB\Type\StringArray;

interface LazyClusterTransactionOperationsInterface
{
    public function getCompareExchangeValue(?string $className, ?string $key, ?Closure $onEval = null): Lazy;

    public function getCompareExchangeValues(?string $className, StringArray|array $keys, ?Closure $onEval = null): Lazy;
}
