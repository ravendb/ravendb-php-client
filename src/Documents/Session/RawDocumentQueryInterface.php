<?php

namespace RavenDB\Documents\Session;

interface RawDocumentQueryInterface
    extends QueryBaseInterface, DocumentQueryBaseSingleInterface, EnumerableQueryInterface
{
    /**
     * Add a named parameter to the query
     */
//    IRawDocumentQuery<T> addParameter(String name, Object value);
//
//    IRawDocumentQuery<T> projection(ProjectionBehavior projectionBehavior);
//
    /**
     * Execute raw query aggregated by facet
     * @return aggregation by facet
     */
//    Map<String, FacetResult> executeAggregation();
}
