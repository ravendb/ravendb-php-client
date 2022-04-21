<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class IndexPriority implements ValueObjectInterface
{
    private const LOW = 'Low';
    private const NORMAL = 'Normal';
    private const HIGH = 'High';

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

    public function isLow(): bool
    {
        return $this->value == self::LOW;
    }

    public static function low(): IndexPriority
    {
        return new IndexPriority(self::LOW);
    }

    public function isNormal(): bool
    {
        return $this->value == self::NORMAL;
    }

    public static function normal(): IndexPriority
    {
        return new IndexPriority(self::NORMAL);
    }

    public function isHigh(): bool
    {
        return $this->value == self::HIGH;
    }

    public static function high(): IndexPriority
    {
        return new IndexPriority(self::HIGH);
    }
}
