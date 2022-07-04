<?php

namespace RavenDB\Documents\Session;

class DocumentQueryCustomizationDelegate implements DocumentQueryCustomizationInterface
{
    private AbstractDocumentQuery $query;

    public function __construct(AbstractDocumentQuery $query)
    {
        $this->query = $query;
    }

    public function getQuery(): AbstractDocumentQuery
    {
        return $this->query;
    }

//    @Override
//    public QueryOperation getQueryOperation() {
//        return query.getQueryOperation();
//    }
//
//    public IDocumentQueryCustomization addBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        query._addBeforeQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization removeBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        query._removeBeforeQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization addAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        query._addAfterQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization removeAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        query._removeAfterQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization addAfterStreamExecutedCallback(Consumer<ObjectNode> action) {
//        query._addAfterStreamExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization removeAfterStreamExecutedCallback(Consumer<ObjectNode> action) {
//        query._removeAfterStreamExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization noCaching() {
//        query._noCaching();
//        return this;
//    }
//

    public function noTracking(): DocumentQueryCustomizationInterface
    {
        $this->query->noTracking();
        return $this;
    }

//    @Override
//    public IDocumentQueryCustomization timings(Reference<QueryTimings> timings) {
//        query._includeTimings(timings);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization randomOrdering() {
//        query._randomOrdering();
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization randomOrdering(String seed) {
//        query._randomOrdering(seed);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization waitForNonStaleResults() {
//        query._waitForNonStaleResults(null);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization waitForNonStaleResults(Duration waitTimeout) {
//        query._waitForNonStaleResults(waitTimeout);
//        return this;
//    }
//
//    @Override
//    public IDocumentQueryCustomization projection(ProjectionBehavior projectionBehavior) {
//        query._projection(projectionBehavior);
//        return this;
//    }
}
