<?php

namespace RavenDB\Documents\Queries\Facets;

//    @todo: implement this
interface AggregationDocumentQueryInterface
{
//    public function andAggregateBy(Consumer<IFacetBuilder<T>> $builder): AggregationDocumentQueryInterface;

    public function andAggregateBy(FacetBase $facet): AggregationDocumentQueryInterface;

    public function execute(): FacetResultArray;

//    Lazy<Map<String, FacetResult>> executeLazy();

//    Lazy<Map<String, FacetResult>> executeLazy(Consumer<Map<String, FacetResult>> onEval);
}
