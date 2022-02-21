<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\ValueObjectInterface;

class FacetAggregation implements ValueObjectInterface
{
    public const NONE = "NONE";
    public const MAX  = "MAX";
    public const MIN = "MIN";
    public const AVERAGE = "AVERAGE";
    public const SUM = "SUM";

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->value;

    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public function isMax(): bool
    {
        return $this->value == self::MAX;
    }

    public function isMin(): bool
    {
        return $this->value == self::MIN;
    }

    public function isAverage(): bool
    {
        return $this->value == self::AVERAGE;
    }

    public function isSum(): bool
    {
        return $this->value == self::SUM;
    }

    public static function none(): FacetAggregation
    {
        return new FacetAggregation(self::NONE);
    }

    public static function max(): FacetAggregation
    {
        return new FacetAggregation(self::MAX);
    }

    public static function min(): FacetAggregation
    {
        return new FacetAggregation(self::MIN);
    }

    public static function average(): FacetAggregation
    {
        return new FacetAggregation(self::AVERAGE);
    }

    public static function sum(): FacetAggregation
    {
        return new FacetAggregation(self::SUM);
    }
}
