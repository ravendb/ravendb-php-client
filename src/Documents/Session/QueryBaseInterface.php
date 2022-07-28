<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Type\Duration;

interface QueryBaseInterface
{
//    /**
//     * Gets the document convention from the query session
//     * @return document conventions
//     */
//    DocumentConventions getConventions();
//
//    TSelf addBeforeQueryExecutedListener(Consumer<IndexQuery> action);
//
//    TSelf removeBeforeQueryExecutedListener(Consumer<IndexQuery> action);
//
//    TSelf addAfterQueryExecutedListener(Consumer<QueryResult> action);
//
//    TSelf removeAfterQueryExecutedListener(Consumer<QueryResult> action);
//
//    TSelf addAfterStreamExecutedListener(Consumer<ObjectNode> action);
//
//    TSelf removeAfterStreamExecutedListener(Consumer<ObjectNode> action);

    function invokeAfterQueryExecuted(QueryResult $result): void;

//    void invokeAfterStreamExecuted(ObjectNode result);

    /**
     * Disables caching for query results.
     *
     * @return static
     */
    function noCaching();

    /**
     * Disables tracking for queried entities by Raven's Unit of Work.
     * Usage of this option will prevent holding query results in memory.
     *
     * @return static
     */
    function noTracking();

//    /**
//     *  Enables calculation of timings for various parts of a query (Lucene search, loading documents, transforming
//     *  results). Default: false
//     * @param timings Reference to output parameter
//     * @return Query instance
//     */
//    TSelf timings(Reference<QueryTimings> timings);
//
    /**
     * Skips the specified count.
     * @param int $count Items to skip
     *
     * @return static
     */
    function skip(int $count);

    /**
     * Provide statistics about the query, such as total count of matching records
     * @param QueryStatistics $stats Output parameter for query stats
     *
     * @return static
     */
    function statistics(QueryStatistics &$stats);

    /**
     * Takes the specified count.
     * @param int $count Amount of items to take
     *
     * @return static
     */
    function take(int $count);

//    /**
//     * Select the default operator to use for this query
//     * @param queryOperator Query operator to use
//     * @return Query instance
//     */
//    TSelf usingDefaultOperator(QueryOperator queryOperator);

    /**
     * EXPERT ONLY: Instructs the query to wait for non stale results for the specified wait timeout.
     * This shouldn't be used outside of unit tests unless you are well aware of the implications
     * @param ?Duration $waitTimeout Max wait timeout
     *
     * @return static
     */
    function waitForNonStaleResults(?Duration $waitTimeout = null);

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
     * @return static
     */
    function addParameter(string $name, $value);

    /**
     * @return array
     */
    function toList(): array;
}
