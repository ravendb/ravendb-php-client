<?php

namespace RavenDB\Documents\Queries\Facets;

use Closure;
use RavenDB\Documents\Lazy;

interface AggregationDocumentQueryInterface
{
    /**
     * @param Closure|FacetBase $builderOrFacets
     * @return AggregationDocumentQueryInterface
     */
    public function andAggregateBy($builderOrFacets): AggregationDocumentQueryInterface;

    public function execute(): FacetResultArray;

    function executeLazy(?Closure $onEval = null): Lazy;
}
