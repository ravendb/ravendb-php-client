<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\ValueObjectInterface;

class SearchOperator implements ValueObjectInterface
{
    /**
     * Or operator will be used between all terms for a search clause, meaning a field value that matches any of the terms will be considered a match
     */
    public const OR = "or";

    /**
     * And operator will be used between all terms for a search clause, meaning a field value matching all of the terms will be considered a match
     */
    private const AND = "and";

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

    public function isOr(): bool
    {
        return $this->value == self::OR;
    }

    public function isAnd(): bool
    {
        return $this->value == self::AND;
    }

    public static function or(): SearchOperator
    {
        return new SearchOperator(self::OR);
    }

    public static function and(): SearchOperator
    {
        return new SearchOperator(self::AND);
    }
}
