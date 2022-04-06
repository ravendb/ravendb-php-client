<?php

namespace RavenDB\Documents\Queries\Facets;

use Symfony\Component\Serializer\Annotation\SerializedName;

abstract class FacetBase
{
    /** @SerializedName("DisplayFieldName") */
    private string $displayFieldName;

    /** @SerializedName("Options") */
    private FacetOptions $options;

    /** @SerializedName("Aggregations") */
//    private Map<FacetAggregation, Set<FacetAggregationField>> $aggregations;

//    public FacetBase() {
//        aggregations = new HashMap<>();
//    }

    public function getDisplayFieldName(): string
    {
        return $this->displayFieldName;
    }

    public function setDisplayFieldName(string $displayFieldName): void
    {
        $this->displayFieldName = $displayFieldName;
    }

    public function getOptions(): FacetOptions
    {
        return $this->options;
    }

    public function setOptions(FacetOptions $options): void
    {
        $this->options = $options;
    }

//    public Map<FacetAggregation, Set<FacetAggregationField>> getAggregations() {
//        return aggregations;
//    }
//
//    public void setAggregations(Map<FacetAggregation, Set<FacetAggregationField>> aggregations) {
//        this.aggregations = aggregations;
//    }
//
//    public abstract function toFacetToken(Function<Object, String> $addQueryParameter): FacetToken;
}
