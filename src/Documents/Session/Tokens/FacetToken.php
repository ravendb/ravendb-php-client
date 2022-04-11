<?php

namespace RavenDB\Documents\Session\Tokens;

use Closure;
use RavenDB\Documents\Queries\Facets\Facet;
use RavenDB\Documents\Queries\Facets\FacetAggregation;
use RavenDB\Documents\Queries\Facets\FacetBase;
use RavenDB\Documents\Queries\Facets\FacetOptions;
use RavenDB\Documents\Queries\Facets\GenericRangeFacet;
use RavenDB\Documents\Queries\Facets\RangeFacet;
use RavenDB\Documents\Queries\QueryFieldUtil;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\NotImplementedException;
use RavenDB\Type\StringArray;
use RavenDB\Utils\StringBuilder;

// !stataus: DONE - TEST REQUIRED
class FacetToken extends QueryToken
{
    private ?string $facetSetupDocumentId = null;
    private ?string $aggregateByFieldName = null;
    private ?string $alias = null;
    private ?StringArray $ranges = null;
    private ?string $optionsParameterName = null;

    private ?FacetAggregationTokenArray $aggregations = null;

    public function getName(): string
    {
        return $this->alias ?? $this->aggregateByFieldName;
    }

    private function __construct()
    {
    }

    public static function createForFacetSetupDocumentId(string $facetSetupDocumentId): FacetToken
    {
        if (empty($facetSetupDocumentId)) {
            throw new IllegalArgumentException("facetSetupDocumentId cannot be null");
        }

        $token = new FacetToken();
        $token->facetSetupDocumentId = $facetSetupDocumentId;

        return $token;
    }

    public static function create(Facet $facet, Closure $addQueryParameter): FacetToken
    {
        $token = new FacetToken();

        $token->aggregateByFieldName = QueryFieldUtil::escapeIfNecessary($facet->getFieldName());
        $token->alias = QueryFieldUtil::escapeIfNecessary($facet->getDisplayFieldName());
        $token->ranges = null;
        $token->optionsParameterName = self::getOptionsParameterName($facet, $addQueryParameter);
        $token->aggregations = new FacetAggregationTokenArray();

        self::applyAggregations($facet, $token);

        return $token;
    }

    public static function createForRangeFacet(RangeFacet $facet, $addQueryParameter): FacetToken
    {
        $token = new FacetToken();

        $token->aggregateByFieldName = null;
        $token->alias = QueryFieldUtil::escapeIfNecessary($facet->getDisplayFieldName());
        $token->ranges = $facet->getRanges();
        $token->optionsParameterName = self::getOptionsParameterName($facet, $addQueryParameter);
        $token->aggregations = new FacetAggregationTokenArray();

        self::applyAggregations($facet, $token);

        return $token;
    }

    public static function createForGenericRangeFacet(GenericRangeFacet $facet, $addQueryParameter): FacetToken
    {
        $ranges = new StringArray();
        foreach ($facet->getRanges() as $rangeBuilder) {
            $ranges->append(GenericRangeFacet::parse($rangeBuilder, $addQueryParameter));
        }

        $token = new FacetToken();

        $token->aggregateByFieldName = null;
        $token->alias = QueryFieldUtil::escapeIfNecessary($facet->getDisplayFieldName());
        $token->ranges = $ranges;
        $token->optionsParameterName = self::getOptionsParameterName($facet, $addQueryParameter);
        $token->aggregations = new FacetAggregationTokenArray();

        self::applyAggregations($facet, $token);

        return $token;
    }

    public static function createForFacetBase(FacetBase $facet, $addQueryParameter): FacetToken
    {
        // this is just a dispatcher
        return $facet->toFacetToken($addQueryParameter);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("facet(");

        if ($this->facetSetupDocumentId != null) {
            $writer->append("id('");
            $writer->append($this->facetSetupDocumentId);
            $writer->append("'))");

            return;
        }

        $firstArgument = false;

        if ($this->aggregateByFieldName != null) {
            $writer->append($this->aggregateByFieldName);
        } else if ($this->ranges != null) {
            $firstInRange = true;

            foreach ($this->ranges as $range) {
                if (!$firstInRange) {
                    $writer->append(", ");
                }

                $firstInRange = false;
                $writer->append($range);
            }
        } else {
            $firstArgument = true;
        }

        /** @var FacetAggregationToken $aggregation */
        foreach ($this->aggregations as $aggregation) {
            if (!$firstArgument) {
                $writer->append(", ");
            }
            $firstArgument = false;
            $aggregation->writeTo($writer);
        }

        if (!empty($this->optionsParameterName)) {
            $writer->append(", $");
            $writer->append($this->optionsParameterName);
        }

        $writer->append(")");

        if (empty($this->alias) || ($this->alias == $this->aggregateByFieldName)) {
            return;
        }

        $writer->append(" as " . $this->alias);
    }

    public function addAggregation(FacetAggregationToken $token): void
    {
        $this->aggregations->append($token);
    }

    private static function applyAggregations(FacetBase $facet, FacetToken &$token): void
    {
        foreach ($facet->getAggregations() as $key => $aggregation) {


            foreach ($aggregation as $value) {
                /** @var FacetAggregationToken $aggregationToken */
                $aggregationToken = null;

                switch ($key) {
                    case FacetAggregation::MAX:
                        $aggregationToken = FacetAggregationToken::max($value->getName(), $value->getDisplayName());
                        break;
                    case FacetAggregation::MIN:
                        $aggregationToken = FacetAggregationToken::min($value->getName(), $value->getDisplayName());
                        break;
                    case FacetAggregation::AVERAGE:
                        $aggregationToken = FacetAggregationToken::average($value->getName(), $value->getDisplayName());
                        break;
                    case FacetAggregation::SUM:
                        $aggregationToken = FacetAggregationToken::sum($value->getName(), $value->getDisplayName());
                        break;
                    default :
                        throw new NotImplementedException("Unsupported aggregation method: " . $key);
                }

                $token->addAggregation($aggregationToken);
            }
        }
    }

    // @todo: check validity of this method with tests in the future when we implement/check Facets
    private static function getOptionsParameterName(FacetBase $facet, Closure $addQueryParameter): ?string
    {
        return ($facet->getOptions() != null) && $facet->getOptions() != FacetOptions::getDefaultOptions() ? $addQueryParameter($facet->getOptions()) : null;
    }
}
