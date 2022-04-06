<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\ValueObjectInterface;

class FacetTermSortMode implements ValueObjectInterface
{
    private const VALUE_ASC = 'ValueAsc';
    private const VALUE_DESC = 'ValueDesc';
    private const COUNT_ASC = 'CountAsc';
    private const COUNT_DESC = 'CountDesc';

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isValueAsc(): bool
    {
        return $this->value == self::VALUE_ASC;
    }

    public function isValueDesc(): bool
    {
        return $this->value == self::VALUE_DESC;
    }

    public function isCountAsc(): bool
    {
        return $this->value == self::COUNT_ASC;
    }

    public function isCountDesc(): bool
    {
        return $this->value == self::COUNT_DESC;
    }

    public static function valueAsc(): FacetTermSortMode
    {
        return new FacetTermSortMode(self::VALUE_ASC);
    }

    public static function valueDesc(): FacetTermSortMode
    {
        return new FacetTermSortMode(self::VALUE_DESC);
    }

    public static function countAsc(): FacetTermSortMode
    {
        return new FacetTermSortMode(self::COUNT_ASC);
    }

    public static function countDesc(): FacetTermSortMode
    {
        return new FacetTermSortMode(self::COUNT_DESC);
    }
}
