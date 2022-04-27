<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Queries\Facets\FacetResultArray;
use RavenDB\Documents\Queries\ProjectionBehavior;

// !status: DONE
interface RawDocumentQueryInterface
    extends QueryBaseInterface, DocumentQueryBaseSingleInterface, EnumerableQueryInterface
{
    /**
     * Add a named parameter to the query
     */
    function addParameter(string $name, $value): RawDocumentQueryInterface;

    function projection(ProjectionBehavior $projectionBehavior): RawDocumentQueryInterface;

    /**
     * Execute raw query aggregated by facet
     * @return FacetResultArray aggregation by facet
     */
    function executeAggregation(): FacetResultArray;
}
