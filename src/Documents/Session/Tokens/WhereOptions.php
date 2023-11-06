<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Type\StringArray;

class WhereOptions
{
    private ?SearchOperator $searchOperator = null;
    private ?string $fromParameterName = null;
    private ?string $toParameterName = null;
    private ?float $boost = null;
    private ?float $fuzzy = null;
    private ?int $proximity = null;
    private bool $exact = false;
    private ?WhereMethodCall $method = null;
    private ?ShapeToken $whereShape = null;
    private ?float $distanceErrorPct = null;

    public static function defaultOptions(): WhereOptions
    {
        return new WhereOptions();
    }

    /**
     * Options can be initialised with following constructions:
     *
     * new WhereOptions();
     * new WhereOptions(bool $exact);
     * new WhereOptions(bool $exact, ?string $from, ?string $to);
     * new WhereOptions(SearchOperator $search);
     * new WhereOptions(ShapeToken $shape, float $distance);
     * new WhereOptions(MethodsType $methodType, StringArray parameters, string $property, boolean $exact = false);
     *
     */
    public function __construct(...$args)
    {
        if (count($args) == 0) {
            return;
        }

        $firstArg = $args[0];

        if (is_bool($firstArg)) {
            $this->exact = $firstArg;
            $from = count($args) > 1 ? $args[1] : null;
            $to = count($args) > 2 ? $args[2] : null;
            $this->initWithExact($firstArg, $from, $to);
            return;
        }

        if ($firstArg instanceof SearchOperator) {
            $this->initWithSearchOperator($firstArg);
            return;
        }

        if ($firstArg instanceof ShapeToken) {
            $this->initWithShapeToken($firstArg, $args[1]);
            return;
        }

        if ($firstArg instanceof MethodsType) {
            $exact = count($args) > 3 ? $args[3] : false;
            $this->initWithMethod($firstArg, $args[1], $args[2], $exact);
        }
    }

    private function initWithExact(bool $exact, ?string $from = null, ?string $to = null): void
    {
        $this->exact = $exact;
        $this->fromParameterName = $from;
        $this->toParameterName = $to;
    }

    private function initWithSearchOperator(SearchOperator $search): void
    {
        $this->searchOperator = $search;
    }

    private function initWithShapeToken(ShapeToken $shape, float $distance): void
    {
        $this->whereShape = $shape;
        $this->distanceErrorPct = $distance;
    }

    private function initWithMethod(MethodsType $methodType, StringArray $parameters, ?string $property, bool $exact): void
    {
        $method = new WhereMethodCall();
        $method->methodType = $methodType;
        $method->parameters = $parameters;
        $method->property = $property;

        $this->method = $method;
        $this->exact = $exact;
    }

    public function getSearchOperator(): ?SearchOperator
    {
        return $this->searchOperator;
    }

    public function setSearchOperator(?SearchOperator $searchOperator): void
    {
        $this->searchOperator = $searchOperator;
    }

    public function getFromParameterName(): ?string
    {
        return $this->fromParameterName;
    }

    public function setFromParameterName(?string $fromParameterName): void
    {
        $this->fromParameterName = $fromParameterName;
    }

    public function getToParameterName(): ?string
    {
        return $this->toParameterName;
    }

    public function setToParameterName(?string $toParameterName): void
    {
        $this->toParameterName = $toParameterName;
    }

    public function getBoost(): ?float
    {
        return $this->boost;
    }

    public function setBoost(?float $boost): void
    {
        $this->boost = $boost;
    }

    public function getFuzzy(): ?float
    {
        return $this->fuzzy;
    }

    public function setFuzzy(?float $fuzzy): void
    {
        $this->fuzzy = $fuzzy;
    }

    public function getProximity(): ?int
    {
        return $this->proximity;
    }

    public function setProximity(?int $proximity): void
    {
        $this->proximity = $proximity;
    }

    public function isExact(): bool
    {
        return $this->exact;
    }

    public function setExact(bool $exact): void
    {
        $this->exact = $exact;
    }

    public function getMethod(): ?WhereMethodCall
    {
        return $this->method;
    }

    public function setMethod(?WhereMethodCall $method): void
    {
        $this->method = $method;
    }

    public function getWhereShape(): ?ShapeToken
    {
        return $this->whereShape;
    }

    public function setWhereShape(?ShapeToken $whereShape): void
    {
        $this->whereShape = $whereShape;
    }

    public function getDistanceErrorPct(): ?float
    {
        return $this->distanceErrorPct;
    }

    public function setDistanceErrorPct(?float $distanceErrorPct): void
    {
        $this->distanceErrorPct = $distanceErrorPct;
    }
}
