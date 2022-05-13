<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRangeArray;
use RavenDB\Documents\Session\Operations\Lazy\EagerSessionOperationsInterface;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;

interface DocumentSessionImplementationInterface extends DocumentSessionInterface, EagerSessionOperationsInterface
{
    public function getConventions(): DocumentConventions;

    public function loadInternal(
        string $className,
        ?StringArray $ids,
        ?StringArray $includes,
        ?StringArray $counterIncludes = null,
        bool $includeAllCounters = false,
        ?AbstractTimeSeriesRangeArray $timeSeriesIncludes = null,
        ?StringArray $compareExchangeValueIncludes = null
    ): ObjectArray;

    // @todo: uncomment this for lazy loading
//    <T> Lazy<Map<String, T>> lazyLoadInternal(Class<T> clazz, String[] ids, String[] includes, Consumer<Map<String, T>> onEval);
}
