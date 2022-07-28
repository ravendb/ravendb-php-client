<?php

namespace RavenDB\Documents\Queries\Facets;

class FacetBuilder implements FacetBuilderInterface, FacetOperationsInterface
{
    private static array $rqlKeywords = [
        "as",
        "select",
        "where",
        "load",
        "group",
        "order",
        "include",
        "update",
    ];

    private ?GenericRangeFacet $range = null;
    private ?Facet $default = null;

    public function byRanges(RangeBuilder $range, RangeBuilderArray $ranges): FacetOperationsInterface
    {
        // TODO: Implement byRanges() method.
    }
//    public IFacetOperations<T> byRanges(RangeBuilder range, RangeBuilder... ranges) {
//        if (range == null) {
//            throw new IllegalArgumentException("Range cannot be null");
//        }
//
//        if (_range == null) {
//            _range = new GenericRangeFacet();
//        }
//
//        _range.getRanges().add(range);
//
//        if (ranges != null) {
//            for (RangeBuilder p : ranges) {
//                _range.getRanges().add(p);
//            }
//        }
//
//        return this;
//    }

    public function byField(string $fieldName): FacetOperationsInterface
    {
        if ($this->default == null) {
            $this->default = new Facet();
        }

        if (in_array($fieldName, self::$rqlKeywords)) {
            $fieldName = "'" . $fieldName . "'";
        }

        $this->default->setFieldName($fieldName);

        return $this;
    }

    public function allResults(): FacetOperationsInterface
    {
        // TODO: Implement allResults() method.
    }
//    public IFacetOperations<T> allResults() {
//        if (_default == null) {
//            _default = new Facet();
//        }
//
//        _default.setFieldName(null);
//        return this;
//    }

    public function withOptions(FacetOptions $options): FacetOperationsInterface
    {
        $this->getFacet()->setOptions($options);
        return $this;
    }

    public function withDisplayName(string $displayName): FacetOperationsInterface
    {
        // todo:
    }
//    public IFacetOperations<T> withDisplayName(String displayName) {
//        getFacet().setDisplayFieldName(displayName);
//        return this;
//    }
//
    public function sumOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {

    }
//    public IFacetOperations<T> sumOn(String path, String displayName) {
//        Map<FacetAggregation, Set<FacetAggregationField>> aggregationsMap = getFacet().getAggregations();
//        Set<FacetAggregationField> aggregations = aggregationsMap.computeIfAbsent(FacetAggregation.SUM, key -> new HashSet<>());
//
//        FacetAggregationField aggregationField = new FacetAggregationField();
//        aggregationField.setName(path);
//        aggregationField.setDisplayName(displayName);
//
//        aggregations.add(aggregationField);
//
//        return this;
//    }
//
    public function minOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {

    }
//    public IFacetOperations<T> minOn(String path, String displayName) {
//        Map<FacetAggregation, Set<FacetAggregationField>> aggregationsMap = getFacet().getAggregations();
//        Set<FacetAggregationField> aggregations = aggregationsMap.computeIfAbsent(FacetAggregation.MIN, key -> new HashSet<>());
//
//        FacetAggregationField aggregationField = new FacetAggregationField();
//        aggregationField.setName(path);
//        aggregationField.setDisplayName(displayName);
//
//        aggregations.add(aggregationField);
//
//        return this;
//    }
//
    public function maxOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {

    }
//    public IFacetOperations<T> maxOn(String path, String displayName) {
//        Map<FacetAggregation, Set<FacetAggregationField>> aggregationsMap = getFacet().getAggregations();
//        Set<FacetAggregationField> aggregations = aggregationsMap.computeIfAbsent(FacetAggregation.MAX, key -> new HashSet<>());
//
//        FacetAggregationField aggregationField = new FacetAggregationField();
//        aggregationField.setName(path);
//        aggregationField.setDisplayName(displayName);
//
//        aggregations.add(aggregationField);
//
//        return this;
//    }
//
    public function averageOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {

    }
//    public IFacetOperations<T> averageOn(String path, String displayName) {
//        Map<FacetAggregation, Set<FacetAggregationField>> aggregationsMap = getFacet().getAggregations();
//        Set<FacetAggregationField> aggregations = aggregationsMap.computeIfAbsent(FacetAggregation.AVERAGE, key -> new HashSet<>());
//
//        FacetAggregationField aggregationField = new FacetAggregationField();
//        aggregationField.setName(path);
//        aggregationField.setDisplayName(displayName);
//
//        aggregations.add(aggregationField);
//
//        return this;
//    }

    public function getFacet(): FacetBase
    {
        if ($this->default != null) {
            return $this->default;
        }

        return $this->range;
    }
}
