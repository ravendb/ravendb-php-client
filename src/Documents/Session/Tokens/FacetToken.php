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

    public static function create(...$parameters): FacetToken
    {
        if (empty($parameters)) {
            throw new IllegalArgumentException('You must define parameters.');
        }

        if (is_string($parameters[0])) {
            return self::createForFacetSetupDocumentId($parameters[0]);
        }

        if (count($parameters) < 2) {
            throw new IllegalArgumentException('You must define all parameters.');
        }

        if ($parameters[0] instanceof Facet) {
            return self::createForFacet($parameters[0], $parameters[1]);
        }

        if ($parameters[0] instanceof RangeFacet) {
            return self::createForRangeFacet($parameters[0], $parameters[1]);
        }

        if ($parameters[0] instanceof GenericRangeFacet) {
            return self::createForGenericRangeFacet($parameters[0], $parameters[1]);
        }

        if ($parameters[0] instanceof FacetBase) {
            return self::createForFacetBase($parameters[0], $parameters[1]);
        }

        throw new IllegalArgumentException('Method called with illegal parameters.');
    }

    private static function createForFacetSetupDocumentId(string $facetSetupDocumentId): FacetToken
    {
        if (empty($facetSetupDocumentId)) {
            throw new IllegalArgumentException("facetSetupDocumentId cannot be null");
        }

        $token = new FacetToken();
        $token->facetSetupDocumentId = $facetSetupDocumentId;

        return $token;
    }

    private static function createForFacet(Facet $facet, Closure $addQueryParameter): FacetToken
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

    private static function createForRangeFacet(RangeFacet $facet, $addQueryParameter): FacetToken
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

    private static function createForGenericRangeFacet(GenericRangeFacet $facet, $addQueryParameter): FacetToken
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

    private static function createForFacetBase(FacetBase $facet, $addQueryParameter): FacetToken
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

    private static function getOptionsParameterName(FacetBase $facet, Closure $addQueryParameter): ?string
    {
        return ($facet->getOptions() != null) && $facet->getOptions() != FacetOptions::getDefaultOptions() ? $addQueryParameter($facet->getOptions()) : null;
    }
}
