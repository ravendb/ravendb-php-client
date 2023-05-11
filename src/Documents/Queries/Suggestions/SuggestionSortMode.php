<?php

namespace RavenDB\Documents\Queries\Suggestions;

use RavenDB\Type\ValueObjectInterface;

class SuggestionSortMode implements ValueObjectInterface
{
    public const NONE = 'None';
    public const POPULARITY = 'Popularity';

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

    public static function none(): SuggestionSortMode
    {
        return new SuggestionSortMode(self::NONE);
    }

    public function isPopularity(): bool
    {
        return $this->value == self::POPULARITY;
    }

    public static function popularity(): SuggestionSortMode
    {
        return new SuggestionSortMode(self::POPULARITY);
    }
}
