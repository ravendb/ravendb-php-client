<?php

namespace RavenDB\Documents\Session;

use Closure;
use DateTime;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRangeSet;
use RavenDB\Documents\Session\Operations\Lazy\EagerSessionOperationsInterface;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;

interface DocumentSessionImplementationInterface extends DocumentSessionInterface, EagerSessionOperationsInterface
{
    public function getConventions(): DocumentConventions;

    public function loadInternal(
        string                      $className,
        ?StringArray                $ids,
        ?StringArray                $includes,
        ?StringArray                $counterIncludes = null,
        bool                        $includeAllCounters = false,
        ?AbstractTimeSeriesRangeSet $timeSeriesIncludes = null,
        ?StringArray                $compareExchangeValueIncludes = null,
        ?StringArray                $revisionsIncludesByChangeVector = null,
        ?DateTime                   $revisionsToIncludeByDateTime = null
    ): ObjectArray;

    public function lazyLoadInternal(?string $className, array|StringArray $ids, array|StringArray $includes, ?Closure $onEval): Lazy;
}
