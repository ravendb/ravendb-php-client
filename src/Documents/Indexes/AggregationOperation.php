<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class AggregationOperation implements ValueObjectInterface
{
    private const NONE = 'None';
    private const COUNT = 'Count';
    private const SUM = 'Sum';

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

    public static function none(): AggregationOperation
    {
        return new AggregationOperation(self::NONE);
    }

    public function isCount(): bool
    {
        return $this->value == self::COUNT;
    }

    public static function count(): AggregationOperation
    {
        return new AggregationOperation(self::COUNT);
    }

    public function isSum(): bool
    {
        return $this->value == self::SUM;
    }

    public static function sum(): AggregationOperation
    {
        return new AggregationOperation(self::SUM);
    }
}
