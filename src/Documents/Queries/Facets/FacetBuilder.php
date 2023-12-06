<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Exceptions\IllegalArgumentException;

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

    public function byRanges(?RangeBuilder $range, ?RangeBuilder ...$ranges): FacetOperationsInterface
    {
        if ($range == null) {
            throw new IllegalArgumentException("Range cannot be null");
        }

        if ($this->range == null) {
            $this->range = new GenericRangeFacet();
        }

        $this->range->getRanges()->append($range);

        if (!empty($ranges)) {
            /** @var RangeBuilder $p */
            foreach ($ranges as $p) {
                $this->range->getRanges()->append($p);
            }
        }

        return $this;
    }

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
        if ($this->default == null) {
            $this->default = new Facet();
        }

        $this->default->setFieldName(null);
        return $this;
    }

    public function withOptions(FacetOptions $options): FacetOperationsInterface
    {
        $this->getFacet()->setOptions($options);
        return $this;
    }

    public function withDisplayName(string $displayName): FacetOperationsInterface
    {
        $this->getFacet()->setDisplayFieldName($displayName);
        return $this;
    }

    public function sumOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {
        $aggregationsMap = $this->getFacet()->getAggregations();

        if (!$aggregationsMap->offsetExists(FacetAggregation::sum()->__toString())) {
            $aggregationsMap->offsetSet(FacetAggregation::sum()->__toString(), new FacetAggregationFieldSet());
        };
        /** @var FacetAggregationFieldSet $aggregations */
        $aggregations = $aggregationsMap->offsetGet(FacetAggregation::sum()->__toString());

        $aggregationField = new FacetAggregationField();
        $aggregationField->setName($path);
        $aggregationField->setDisplayName($displayName);

        $aggregations->append($aggregationField);

        return $this;
    }

    public function minOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {
        $aggregationsMap = $this->getFacet()->getAggregations();

        if (!$aggregationsMap->offsetExists(FacetAggregation::min()->__toString())) {
            $aggregationsMap->offsetSet(FacetAggregation::min()->__toString(), new FacetAggregationFieldSet());
        };
        /** @var FacetAggregationFieldSet $aggregations */
        $aggregations = $aggregationsMap->offsetGet(FacetAggregation::min()->__toString());

        $aggregationField = new FacetAggregationField();
        $aggregationField->setName($path);
        $aggregationField->setDisplayName($displayName);

        $aggregations->append($aggregationField);

        return $this;
    }

    public function maxOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {
        $aggregationsMap = $this->getFacet()->getAggregations();

        if (!$aggregationsMap->offsetExists(FacetAggregation::max()->__toString())) {
            $aggregationsMap->offsetSet(FacetAggregation::max()->__toString(), new FacetAggregationFieldSet());
        };
        /** @var FacetAggregationFieldSet $aggregations */
        $aggregations = $aggregationsMap->offsetGet(FacetAggregation::max()->__toString());

        $aggregationField = new FacetAggregationField();
        $aggregationField->setName($path);
        $aggregationField->setDisplayName($displayName);

        $aggregations->append($aggregationField);

        return $this;
    }

    public function averageOn(string $path, ?string $displayName = null): FacetOperationsInterface
    {
        $aggregationsMap = $this->getFacet()->getAggregations();

        if (!$aggregationsMap->offsetExists(FacetAggregation::average()->__toString())) {
            $aggregationsMap->offsetSet(FacetAggregation::average()->__toString(), new FacetAggregationFieldSet());
        };
        /** @var FacetAggregationFieldSet $aggregations */
        $aggregations = $aggregationsMap->offsetGet(FacetAggregation::average()->__toString());

        $aggregationField = new FacetAggregationField();
        $aggregationField->setName($path);
        $aggregationField->setDisplayName($displayName);

        $aggregations->append($aggregationField);

        return $this;
    }

    public function getFacet(): FacetBase
    {
        if ($this->default != null) {
            return $this->default;
        }

        return $this->range;
    }
}
