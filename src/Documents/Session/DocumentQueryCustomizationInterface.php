<?php

namespace RavenDB\Documents\Session;

interface DocumentQueryCustomizationInterface
{
    /**
     * Get the raw query operation that will be sent to the server
     * @return Query operation
     */
//    QueryOperation getQueryOperation();

    /**
     * Get current Query
     * @return AbstractDocumentQuery
     */
    public function getQuery(): AbstractDocumentQuery;

    /**
     * Allow you to modify the index query before it is executed
     * @param action action to call
     * @return customization object
     */
//    IDocumentQueryCustomization addBeforeQueryExecutedListener(Consumer<IndexQuery> action);

    /**
     * Allow you to modify the index query before it is executed
     * @param action action to call
     * @return customization object
     */
//    IDocumentQueryCustomization removeBeforeQueryExecutedListener(Consumer<IndexQuery> action);

    /**
     * Callback to get the results of the query
     * @param action action to call
     * @return customization object
     */
//    IDocumentQueryCustomization addAfterQueryExecutedListener(Consumer<QueryResult> action);

    /**
     * Callback to get the results of the query
     * @param action action to call
     * @return customization object
     */
//    IDocumentQueryCustomization removeAfterQueryExecutedListener(Consumer<QueryResult> action);


    /**
     * Callback to get the raw objects streamed by the query
     * @param action action to call
     * @return customization object
     */
//    IDocumentQueryCustomization addAfterStreamExecutedCallback(Consumer<ObjectNode> action);

    /**
     * Callback to get the raw objects streamed by the query
     * @param action action to call
     * @return customization object
     */
//    IDocumentQueryCustomization removeAfterStreamExecutedCallback(Consumer<ObjectNode> action);

    /**
     * Disables caching for query results.
     * @return customization object
     */
//    IDocumentQueryCustomization noCaching();

    /**
     * Disables tracking for queried entities by Raven's Unit of Work.
     * Usage of this option will prevent holding query results in memory.
     * @return DocumentQueryCustomizationInterface customization object
     */
    function noTracking(): DocumentQueryCustomizationInterface;

    /**
     * Disables tracking for queried entities by Raven's Unit of Work.
     * Usage of this option will prevent holding query results in memory.
     * @return customization object
     */
//    IDocumentQueryCustomization randomOrdering();

    /**
     *  Order the search results randomly using the specified seed
     *  this is useful if you want to have repeatable random queries
     * @param seed Random seed
     * @return customization object
     */
//    IDocumentQueryCustomization randomOrdering(String seed);

    //TBD 4.1 IDocumentQueryCustomization CustomSortUsing(string typeName);
    //TBD 4.1 IDocumentQueryCustomization CustomSortUsing(string typeName, bool descending);

//    IDocumentQueryCustomization timings(Reference<QueryTimings> timings);

    /**
     * Instruct the query to wait for non stale results.
     * This shouldn't be used outside of unit tests unless you are well aware of the implications
     * @return customization object
     */
//    IDocumentQueryCustomization waitForNonStaleResults();

    /**
     * Instruct the query to wait for non stale results.
     * This shouldn't be used outside of unit tests unless you are well aware of the implications
     * @param waitTimeout Maximum time to wait for index query results to become non-stale before exception is thrown. Default: 15 seconds.
     * @return customization object
     */
//    IDocumentQueryCustomization waitForNonStaleResults(Duration waitTimeout);

//    IDocumentQueryCustomization projection(ProjectionBehavior projectionBehavior);
}
