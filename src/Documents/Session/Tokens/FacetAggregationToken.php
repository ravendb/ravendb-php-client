<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Queries\Facets\FacetAggregation;
use RavenDB\Exceptions\IllegalArgumentException;

class FacetAggregationToken extends QueryToken
{
    private string $fieldName;
    private ?string $fieldDisplayName;
    private FacetAggregation $aggregation;

    private function __construct(string $fieldName, ?string $fieldDisplayName, FacetAggregation $aggregation)
    {
        $this->fieldName = $fieldName;
        $this->fieldDisplayName = $fieldDisplayName;
        $this->aggregation = $aggregation;
    }

    public function writeTo(): string
    {
        $result = '';

        switch ($this->aggregation) {
            case FacetAggregation::MAX:
                $result .= "max(" . $this->fieldName . ")";
                break;
            case FacetAggregation::MIN:
                $result .= "min(" . $this->fieldName . ")";
                break;
            case FacetAggregation::AVERAGE:
                $result .= "avg(" . $this->fieldName . ")";
                break;
            case FacetAggregation::SUM:
                $result .= "sum(" . $this->fieldName . ")";
                break;
            default:
                throw new IllegalArgumentException("Invalid aggregation mode: " . $this->aggregation);
        }

        if (!empty($this->fieldDisplayName)) {
            $result .= " as " . $this->fieldDisplayName;
        }

        return $result;
    }

    public static function max(string $fieldName, ?string $fieldDisplayName = null): FacetAggregationToken
    {
        if (empty($fieldName)) {
            throw new IllegalArgumentException("FieldName can not be null");
        }
        return new FacetAggregationToken($fieldName, $fieldDisplayName, FacetAggregation::max());
    }

    public static function min(string $fieldName, ?string $fieldDisplayName = null): FacetAggregationToken
    {
        if (empty($fieldName)) {
            throw new IllegalArgumentException("FieldName can not be null");
        }
        return new FacetAggregationToken($fieldName, $fieldDisplayName, FacetAggregation::min());
    }

    public static function average(string $fieldName, ?string $fieldDisplayName = null): FacetAggregationToken
    {
        if (empty($fieldName)) {
            throw new IllegalArgumentException("FieldName can not be null");
        }
        return new FacetAggregationToken($fieldName, $fieldDisplayName, FacetAggregation::average());
    }

    public static function sum(string $fieldName, ?string $fieldDisplayName = null): FacetAggregationToken
    {
        if (empty($fieldName)) {
            throw new IllegalArgumentException("FieldName can not be null");
        }
        return new FacetAggregationToken($fieldName, $fieldDisplayName, FacetAggregation::sum());
    }
}
