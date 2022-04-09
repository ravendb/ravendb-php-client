<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\ValueObjectInterface;

class WhereOperator implements ValueObjectInterface
{
//    EQUALS,
//    NOT_EQUALS,
//    GREATER_THAN,
//    GREATER_THAN_OR_EQUAL,
//    LESS_THAN,
//    LESS_THAN_OR_EQUAL,
//    IN,
//    ALL_IN,
//    BETWEEN,
    private const SEARCH = 'search';
//    LUCENE,
//    STARTS_WITH,
//    ENDS_WITH,
    private const EXISTS = 'exists';
//    SPATIAL_WITHIN,
//    SPATIAL_CONTAINS,
//    SPATIAL_DISJOINT,
//    SPATIAL_INTERSECTS,
//    REGEX

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

     public function isSearch(): bool
     {
         return $this->value == self::SEARCH;
     }

    public function isExists(): bool
    {
        return $this->value == self::EXISTS;
    }

    public static function search(): WhereOperator
    {
        return new WhereOperator(self::SEARCH);
    }

    public static function exists(): WhereOperator
    {
        return new WhereOperator(self::EXISTS);
    }
}
