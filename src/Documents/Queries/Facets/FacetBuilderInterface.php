<?php

namespace RavenDB\Documents\Queries\Facets;


interface FacetBuilderInterface
{
    public function byRanges(RangeBuilder $range, RangeBuilder ...$ranges): FacetOperationsInterface;

    public function byField(String $fieldName): FacetOperationsInterface;

    public function allResults(): FacetOperationsInterface;

    //TBD expr IFacetOperations<T> ByField(Expression<Func<T, object>> path);
    //TBD expr IFacetOperations<T> ByRanges(Expression<Func<T, bool>> path, params Expression<Func<T, bool>>[] paths);
}
