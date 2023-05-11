<?php

namespace RavenDB\Documents\Queries\Suggestions;

use RavenDB\Type\ValueObjectInterface;

class StringDistanceTypes implements ValueObjectInterface
{
    public const NONE = 'None';
    public const DEFAULT = 'Default';
    public const LEVENSHTEIN = 'Levenshtein';
    public const JARO_WINKLER = 'JaroWinkler';
    public const N_GRAM = 'NGram';

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

    public static function none(): StringDistanceTypes
    {
        return new StringDistanceTypes(self::NONE);
    }

    public function isDefault(): bool
    {
        return $this->value == self::DEFAULT;
    }

    public static function default(): StringDistanceTypes
    {
        return new StringDistanceTypes(self::DEFAULT);
    }

    public function isLevenshtein(): bool
    {
        return $this->value == self::LEVENSHTEIN;
    }

    public static function levenshtein(): StringDistanceTypes
    {
        return new StringDistanceTypes(self::LEVENSHTEIN);
    }

    public function isJaroWinkler(): bool
    {
        return $this->value == self::JARO_WINKLER;
    }

    public static function jaroWinkler(): StringDistanceTypes
    {
        return new StringDistanceTypes(self::JARO_WINKLER);
    }

    public function isNGram(): bool
    {
        return $this->value == self::N_GRAM;
    }

    public static function nGram(): StringDistanceTypes
    {
        return new StringDistanceTypes(self::N_GRAM);
    }
}
