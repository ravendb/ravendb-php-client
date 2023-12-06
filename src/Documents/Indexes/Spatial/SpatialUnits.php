<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\ValueObjectInterface;

class SpatialUnits implements ValueObjectInterface
{
    private const KILOMETERS = 'Kilometers';
    private const MILES = 'Miles';

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

    public function isKilometers(): bool
    {
        return $this->value == self::KILOMETERS;
    }

    public static function kilometers(): SpatialUnits
    {
        return new SpatialUnits(self::KILOMETERS);
    }

    public function isMiles(): bool
    {
        return $this->value == self::MILES;
    }

    public static function miles(): SpatialUnits
    {
        return new SpatialUnits(self::MILES);
    }
}

