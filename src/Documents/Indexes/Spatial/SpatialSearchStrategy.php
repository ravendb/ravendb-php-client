<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\ValueObjectInterface;

class SpatialSearchStrategy implements ValueObjectInterface
{
    private const GEOHASH_PREFIX_TREE = 'GeohashPrefixTree';
    private const QUAD_PREFIX_TREE = 'QuadPrefixTree';
    private const BOUNDING_BOX = 'BoundingBox';

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

    public function isGeohashPrefixTree(): bool
    {
        return $this->value == self::GEOHASH_PREFIX_TREE;
    }

    public static function geohashPrefixTree(): SpatialSearchStrategy
    {
        return new SpatialSearchStrategy(self::GEOHASH_PREFIX_TREE);
    }

    public function isQuadPrefixTree(): bool
    {
        return $this->value == self::QUAD_PREFIX_TREE;
    }

    public static function quadPrefixTree(): SpatialSearchStrategy
    {
        return new SpatialSearchStrategy(self::QUAD_PREFIX_TREE);
    }

    public function isBoundingBox(): bool
    {
        return $this->value == self::BOUNDING_BOX;
    }

    public static function boundingBox(): SpatialSearchStrategy
    {
        return new SpatialSearchStrategy(self::BOUNDING_BOX);
    }
}
