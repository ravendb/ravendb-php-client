<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class IndexSourceType implements ValueObjectInterface
{
    private const NONE = 'None';
    private const DOCUMENTS = 'Documents';
    private const TIME_SERIES = 'TimeSeries';
    private const COUNTERS = 'Counters';

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

    public static function none(): IndexSourceType
    {
        return new IndexSourceType(self::NONE);
    }

    public function isDocuments(): bool
    {
        return $this->value == self::DOCUMENTS;
    }

    public static function documents(): IndexSourceType
    {
        return new IndexSourceType(self::DOCUMENTS);
    }

    public function isTimeSeries(): bool
    {
        return $this->value == self::TIME_SERIES;
    }

    public static function timeSeries(): IndexSourceType
    {
        return new IndexSourceType(self::TIME_SERIES);
    }

    public function isCounters(): bool
    {
        return $this->value == self::COUNTERS;
    }

    public static function counters(): IndexSourceType
    {
        return new IndexSourceType(self::COUNTERS);
    }
}
