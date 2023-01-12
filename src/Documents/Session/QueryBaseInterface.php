<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryOperator;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Queries\Timings\QueryTimings;
use RavenDB\Type\Duration;

interface QueryBaseInterface
{
    /**
     * Gets the document convention from the query session
     * @return DocumentConventions document conventions
     */
    function getConventions(): DocumentConventions;

    public function addBeforeQueryExecutedListener(Closure $action): QueryBaseInterface;

    public function removeBeforeQueryExecutedListener(Closure $action): QueryBaseInterface;

    public function addAfterQueryExecutedListener(Closure $action): QueryBaseInterface;

    public function removeAfterQueryExecutedListener(Closure $action): QueryBaseInterface;

    public function addAfterStreamExecutedListener(Closure $action): QueryBaseInterface;

    public function removeAfterStreamExecutedListener(Closure $action): QueryBaseInterface;

    function invokeAfterQueryExecuted(QueryResult $result): void;

    public function invokeAfterStreamExecuted($result): void;

    /**
     * Disables caching for query results.
     *
     * @return QueryBaseInterface;
     */
    function noCaching(): QueryBaseInterface;

    /**
     * Disables tracking for queried entities by Raven's Unit of Work.
     * Usage of this option will prevent holding query results in memory.
     *
     * @return QueryBaseInterface;
     */
    function noTracking(): QueryBaseInterface;

    /**
     *  Enables calculation of timings for various parts of a query (Lucene search, loading documents, transforming
     *  results). Default: false
     * @param QueryTimings $timings Reference to output parameter
     * @return QueryBaseInterface Query instance
     */
    function timings(QueryTimings &$timings): QueryBaseInterface;

    /**
     * Skips the specified count.
     * @param int $count Items to skip
     *
     * @return QueryBaseInterface
     */
    function skip(int $count): QueryBaseInterface;

    /**
     * Provide statistics about the query, such as total count of matching records
     * @param QueryStatistics $stats Output parameter for query stats
     *
     * @return QueryBaseInterface
     */
    function statistics(QueryStatistics &$stats): QueryBaseInterface;

    /**
     * Takes the specified count.
     * @param int $count Amount of items to take
     *
     * @return QueryBaseInterface
     */
    function take(int $count): QueryBaseInterface;

    /**
     * Select the default operator to use for this query
     * @param QueryOperator $queryOperator Query operator to use
     * @return QueryBaseInterface Query instance
     */
    function usingDefaultOperator(QueryOperator $queryOperator): QueryBaseInterface;

    /**
     * EXPERT ONLY: Instructs the query to wait for non stale results for the specified wait timeout.
     * This shouldn't be used outside of unit tests unless you are well aware of the implications
     * @param ?Duration $waitTimeout Max wait timeout
     *
     * @return QueryBaseInterface
     */
    function waitForNonStaleResults(?Duration $waitTimeout = null): QueryBaseInterface;

    /**
     * Create the index query object for this query
     *
     * @return IndexQuery index query
     */
    function getIndexQuery(): IndexQuery;

    /**
     * Add a named parameter to the query
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     *
     * @return QueryBaseInterface
     */
    function addParameter(string $name, $value): QueryBaseInterface;

    /**
     * @return array
     */
    function toList(): array;
}
