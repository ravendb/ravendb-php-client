<?php

namespace RavenDB\Documents\Queries\Facets;

use Closure;
use RavenDB\Documents\Session\Tokens\FacetToken;
use Symfony\Component\Serializer\Annotation\SerializedName;


abstract class FacetBase
{
    /** @SerializedName("DisplayFieldName") */
    private ?string $displayFieldName = null;

    /** @SerializedName("Options") */
    private ?FacetOptions $options = null;

    /** @SerializedName("Aggregations") */
    private ?AggregationArray $aggregations = null;

    public function __construct()
    {
        $this->aggregations = new AggregationArray();
    }

    public function getDisplayFieldName(): ?string
    {
        return $this->displayFieldName;
    }

    public function setDisplayFieldName(?string $displayFieldName): void
    {
        $this->displayFieldName = $displayFieldName;
    }

    public function getOptions(): ?FacetOptions
    {
        return $this->options;
    }

    public function setOptions(?FacetOptions $options): void
    {
        $this->options = $options;
    }

    public function getAggregations(): ?AggregationArray
    {
        return $this->aggregations;
    }

    public function setAggregations(?AggregationArray $aggregations): void
    {
        $this->aggregations = $aggregations;
    }

    public abstract function toFacetToken(Closure $addQueryParameter): FacetToken;
}
