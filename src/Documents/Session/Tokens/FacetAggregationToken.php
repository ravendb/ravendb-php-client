<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Queries\Facets\FacetAggregation;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringBuilder;

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

    public function writeTo(StringBuilder &$writer): void
    {
        switch ($this->aggregation) {
            case FacetAggregation::MAX:
                $writer->append("max(" . $this->fieldName . ")");
                break;
            case FacetAggregation::MIN:
                $writer->append("min(" . $this->fieldName . ")");
                break;
            case FacetAggregation::AVERAGE:
                $writer->append("avg(" . $this->fieldName . ")");
                break;
            case FacetAggregation::SUM:
                $writer->append("sum(" . $this->fieldName . ")");
                break;
            default:
                throw new IllegalArgumentException("Invalid aggregation mode: " . $this->aggregation);
        }

        if (empty($this->fieldDisplayName)) {
            return;
        }

        $writer->append(" as " . $this->fieldDisplayName);
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
