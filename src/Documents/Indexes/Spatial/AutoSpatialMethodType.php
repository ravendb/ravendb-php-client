<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\ValueObjectInterface;

class AutoSpatialMethodType implements ValueObjectInterface
{
    private const POINT = 'point';
    private const WKT = 'wkt';

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

    public function isPoint(): bool
    {
        return $this->value == self::POINT;
    }

    public static function point(): AutoSpatialMethodType
    {
        return new AutoSpatialMethodType(self::POINT);
    }

    public function isWkt(): bool
    {
        return $this->value == self::WKT;
    }

    public static function wkt(): AutoSpatialMethodType
    {
        return new AutoSpatialMethodType(self::WKT);
    }
}
