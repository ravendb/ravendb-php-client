<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\ValueObjectInterface;

class SpatialFieldType implements ValueObjectInterface
{
    private const GEOGRAPHY = 'Geography';
    private const CARTESIAN = 'Cartesian';

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

    public function isGeography(): bool
    {
        return $this->value == self::GEOGRAPHY;
    }

    public static function geography(): SpatialFieldType
    {
        return new SpatialFieldType(self::GEOGRAPHY);
    }

    public function isCartesian(): bool
    {
        return $this->value == self::CARTESIAN;
    }

    public static function cartesian(): SpatialFieldType
    {
        return new SpatialFieldType(self::CARTESIAN);
    }
}
