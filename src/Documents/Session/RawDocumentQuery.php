<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Documents\Queries\Facets\AggregationRawDocumentQuery;
use RavenDB\Documents\Queries\Facets\FacetResultArray;
use RavenDB\Documents\Queries\ProjectionBehavior;
use RavenDB\Documents\Queries\QueryOperator;
use RavenDB\Documents\Queries\Timings\QueryTimings;
use RavenDB\Type\Duration;

class RawDocumentQuery extends AbstractDocumentQuery implements RawDocumentQueryInterface
{
    public function __construct(?string $className, InMemoryDocumentSessionOperations $session, string $rawQuery)
    {
        parent::__construct($className, $session, null, null, false, null, null);
        $this->queryRaw = $rawQuery;
    }

    public function skip(int $count): RawDocumentQueryInterface
    {
        $this->_skip($count);
        return $this;
    }

    public function take(int $count): RawDocumentQueryInterface
    {
        $this->_take($count);
        return $this;
    }

    public function waitForNonStaleResults(?Duration $waitTimeout = null): RawDocumentQueryInterface
    {
        $this->_waitForNonStaleResults($waitTimeout);
        return $this;
    }


    public function timings(QueryTimings &$timings): RawDocumentQueryInterface
    {
        $this->_includeTimings($timings);
        return $this;
    }

    public function noTracking(): RawDocumentQueryInterface
    {
        $this->_noTracking();
        return $this;
    }

    public function noCaching(): RawDocumentQueryInterface
    {
        $this->_noCaching();
        return $this;
    }

    public function usingDefaultOperator(QueryOperator $queryOperator): RawDocumentQueryInterface
    {
        $this->_usingDefaultOperator($queryOperator);
        return $this;
    }

    public function statistics(QueryStatistics &$stats): RawDocumentQueryInterface
    {
        $this->_statistics($stats);
        return $this;
    }

    public function addAfterQueryExecutedListener(Closure $action): RawDocumentQueryInterface
    {
        $this->_addAfterQueryExecutedListener($action);
        return $this;
    }

    /**
     * @param Closure $action
     * @return RawDocumentQueryInterface
     */
    public function removeAfterQueryExecutedListener(Closure $action): RawDocumentQueryInterface
    {
        $this->_removeAfterQueryExecutedListener($action);
        return $this;
    }

    /**
     * @param Closure $action
     * @return RawDocumentQueryInterface
     */
    public function addBeforeQueryExecutedListener(Closure $action): RawDocumentQueryInterface
    {
        $this->_addBeforeQueryExecutedListener($action);
        return $this;
    }

    /**
     * @param Closure $action
     * @return $this|RawDocumentQueryInterface
     */
    public function removeBeforeQueryExecutedListener(Closure $action): RawDocumentQueryInterface
    {
        $this->_removeBeforeQueryExecutedListener($action);
        return $this;
    }

    public function addAfterStreamExecutedListener(Closure $action): RawDocumentQueryInterface
    {
        $this->_addAfterStreamExecutedListener($action);
        return $this;
    }

    public function removeAfterStreamExecutedListener(Closure $action): RawDocumentQueryInterface
    {
        $this->_removeAfterStreamExecutedListener($action);
        return $this;
    }

    public function addParameter(string $name, $value): RawDocumentQueryInterface
    {
        $this->_addParameter($name, $value);
        return $this;
    }

    public function executeAggregation(): FacetResultArray
    {
        $query = new AggregationRawDocumentQuery($this, $this->theSession);
        return $query->execute();
    }

    public function projection(ProjectionBehavior $projectionBehavior): RawDocumentQuery
    {
        $this->_projection($projectionBehavior);
        return $this;
    }
}
