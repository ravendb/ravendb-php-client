<?php

namespace RavenDB\Documents\Session;

// !status: IN PROGRESS
class RawDocumentQuery extends AbstractDocumentQuery implements RawDocumentQueryInterface
{
    public function __construct(string $className, InMemoryDocumentSessionOperations $session, string $rawQuery)
    {
        parent::__construct($className, $session, null, null, false, null, null);
        $this->queryRaw = $rawQuery;
    }

//    public IRawDocumentQuery<T> skip(int count) {
//        _skip(count);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> take(int count) {
//        _take(count);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> waitForNonStaleResults() {
//        _waitForNonStaleResults(null);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> waitForNonStaleResults(Duration waitTimeout) {
//        _waitForNonStaleResults(waitTimeout);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> timings(Reference<QueryTimings> timings) {
//        _includeTimings(timings);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> noTracking() {
//        _noTracking();
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> noCaching() {
//        _noCaching();
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> usingDefaultOperator(QueryOperator queryOperator) {
//        _usingDefaultOperator(queryOperator);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> statistics(Reference<QueryStatistics> stats) {
//        _statistics(stats);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> removeAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        _removeAfterQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> addAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        _addAfterQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> addBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        _addBeforeQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> removeBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        _removeBeforeQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> addAfterStreamExecutedListener(Consumer<ObjectNode> action) {
//        _addAfterStreamExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> removeAfterStreamExecutedListener(Consumer<ObjectNode> action) {
//        _removeAfterStreamExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IRawDocumentQuery<T> addParameter(String name, Object value) {
//        _addParameter(name, value);
//        return this;
//    }
//
//    @Override
//    public Map<String, FacetResult> executeAggregation() {
//        AggregationRawDocumentQuery<T> query = new AggregationRawDocumentQuery<>(this, theSession);
//        return query.execute();
//    }
//
//    @Override
//    public IRawDocumentQuery<T> projection(ProjectionBehavior projectionBehavior) {
//        _projection(projectionBehavior);
//        return this;
//    }
}
