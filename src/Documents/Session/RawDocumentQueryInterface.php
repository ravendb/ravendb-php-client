<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Documents\Queries\Facets\FacetResultArray;
use RavenDB\Documents\Queries\ProjectionBehavior;
use RavenDB\Type\Duration;

interface RawDocumentQueryInterface
    extends QueryBaseInterface, DocumentQueryBaseSingleInterface, EnumerableQueryInterface
{
    /**
     * Add a named parameter to the query
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     *
     * @return RawDocumentQueryInterface
     */
    function addParameter(string $name, $value): RawDocumentQueryInterface;

    function projection(ProjectionBehavior $projectionBehavior): RawDocumentQueryInterface;

    /**
     * Execute raw query aggregated by facet
     * @return FacetResultArray aggregation by facet
     */
    function executeAggregation(): FacetResultArray;


    public function addBeforeQueryExecutedListener(Closure $action): RawDocumentQueryInterface;

    public function removeBeforeQueryExecutedListener(Closure $action): RawDocumentQueryInterface;

    public function addAfterQueryExecutedListener(Closure $action): RawDocumentQueryInterface;

    public function removeAfterQueryExecutedListener(Closure $action): RawDocumentQueryInterface;

    public function addAfterStreamExecutedListener(Closure $action): RawDocumentQueryInterface;

    public function removeAfterStreamExecutedListener(Closure $action): RawDocumentQueryInterface;

    function noCaching(): RawDocumentQueryInterface;

    function noTracking(): RawDocumentQueryInterface;

    function skip(int $count): RawDocumentQueryInterface;
    function statistics(QueryStatistics &$stats): RawDocumentQueryInterface;
    function take(int $count): RawDocumentQueryInterface;
    function waitForNonStaleResults(?Duration $waitTimeout = null): RawDocumentQueryInterface;
}
