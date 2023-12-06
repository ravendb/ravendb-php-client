<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\ValueObjectInterface;

class SpatialRelation implements ValueObjectInterface
{
    public const WITHIN = 'within';
    public const CONTAINS = 'contains';
    public const DISJOINT = 'disjoint';
    public const INTERSECTS = 'intersects';

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

    public function isWithin(): bool
    {
        return $this->value == self::WITHIN;
    }

    public static function within(): SpatialRelation
    {
        return new SpatialRelation(self::WITHIN);
    }

    public function isContains(): bool
    {
        return $this->value == self::CONTAINS;
    }

    public static function contains(): SpatialRelation
    {
        return new SpatialRelation(self::CONTAINS);
    }

    public function isDisjoint(): bool
    {
        return $this->value == self::DISJOINT;
    }

    public static function disjoint(): SpatialRelation
    {
        return new SpatialRelation(self::DISJOINT);
    }

    public function isIntersects(): bool
    {
        return $this->value == self::INTERSECTS;
    }

    public static function intersects(): SpatialRelation
    {
        return new SpatialRelation(self::INTERSECTS);
    }
}
