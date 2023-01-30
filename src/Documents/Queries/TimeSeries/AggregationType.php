<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use RavenDB\Type\ValueObjectInterface;

class AggregationType implements ValueObjectInterface
{
    // The order here matters.
    // When executing an aggregation query over rolled-up series,
    // we take just the appropriate aggregated value from each entry,
    // according to the aggregation's position in this enum (e.g. AggregationType.Min => take entry.Values[2])


    private const FIRST = 'First';
    private const LAST = 'Last';
    private const MIN = 'Min';
    private const MAX = 'Max';
    private const SUM = 'Sum';
    private const COUNT = 'Count';
    private const AVERAGE = 'Average';
    private const PERCENTILE = 'Percentile';
    private const SLOPE = 'Slope';
    private const STANDARD_DEVIATION = 'StandardDeviation';

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isFirst(): bool
    {
        return $this->value == self::FIRST;
    }

    public static function first(): AggregationType
    {
        return new AggregationType(self::FIRST);
    }

    public function isLast(): bool
    {
        return $this->value == self::LAST;
    }

    public static function last(): AggregationType
    {
        return new AggregationType(self::LAST);
    }

    public function isMin(): bool
    {
        return $this->value == self::MIN;
    }

    public static function min(): AggregationType
    {
        return new AggregationType(self::MIN);
    }

    public function isMax(): bool
    {
        return $this->value == self::MAX;
    }

    public static function max(): AggregationType
    {
        return new AggregationType(self::MAX);
    }

    public function isSum(): bool
    {
        return $this->value == self::SUM;
    }

    public static function sum(): AggregationType
    {
        return new AggregationType(self::SUM);
    }

    public function isCount(): bool
    {
        return $this->value == self::COUNT;
    }

    public static function count(): AggregationType
    {
        return new AggregationType(self::COUNT);
    }

    public function isAverage(): bool
    {
        return $this->value == self::AVERAGE;
    }

    public static function average(): AggregationType
    {
        return new AggregationType(self::AVERAGE);
    }

    public function isPercentile(): bool
    {
        return $this->value == self::PERCENTILE;
    }

    public static function percentile(): AggregationType
    {
        return new AggregationType(self::PERCENTILE);
    }

    public function isSlope(): bool
    {
        return $this->value == self::SLOPE;
    }

    public static function slope(): AggregationType
    {
        return new AggregationType(self::SLOPE);
    }

    public function isStandardDeviation(): bool
    {
        return $this->value == self::STANDARD_DEVIATION;
    }

    public static function standardDeviation(): AggregationType
    {
        return new AggregationType(self::STANDARD_DEVIATION);
    }
}
