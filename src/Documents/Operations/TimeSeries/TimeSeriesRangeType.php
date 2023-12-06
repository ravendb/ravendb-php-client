<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Type\ValueObjectInterface;

class TimeSeriesRangeType implements ValueObjectInterface
{
    public const NONE = 'None';
    public const LAST = 'Last';

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

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public function isLast(): bool
    {
        return $this->value == self::LAST;
    }

    public static function none(): TimeSeriesRangeType
    {
        return new TimeSeriesRangeType(self::NONE);
    }

    public static function last(): TimeSeriesRangeType
    {
        return new TimeSeriesRangeType(self::LAST);
    }
}
