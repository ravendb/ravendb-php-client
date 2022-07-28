<?php

namespace RavenDB\Documents\Queries\Facets;

//    @todo: implement this
interface AggregationDocumentQueryInterface
{
    /**
     * @param Callable|FacetBase $builderOrFacets
     * @return AggregationDocumentQueryInterface
     */
    public function andAggregateBy($builderOrFacets): AggregationDocumentQueryInterface;

    public function execute(): FacetResultArray;

//    Lazy<Map<String, FacetResult>> executeLazy();

//    Lazy<Map<String, FacetResult>> executeLazy(Consumer<Map<String, FacetResult>> onEval);
}
