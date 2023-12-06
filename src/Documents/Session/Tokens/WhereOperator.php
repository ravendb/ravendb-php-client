<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\ValueObjectInterface;

class WhereOperator implements ValueObjectInterface
{
    public const EQUALS = 'equals';
    public const NOT_EQUALS = 'not equals';
    public const GREATER_THAN = 'greater than';
    public const GREATER_THAN_OR_EQUAL = 'greater that or equal';
    public const LESS_THAN = 'less than';
    public const LESS_THAN_OR_EQUAL = 'less than or equal';
    public const IN = 'in';
    public const ALL_IN = 'all in';
    public const BETWEEN = 'between';
    public const SEARCH = 'search';
    public const LUCENE = 'lucene';
    public const STARTS_WITH = 'starts with';
    public const ENDS_WITH = 'ends with';
    public const EXISTS = 'exists';
    public const SPATIAL_WITHIN = 'spatial within';
    public const SPATIAL_CONTAINS = 'spatial contains';
    public const SPATIAL_DISJOINT = 'spatial disjoint';
    public const SPATIAL_INTERSECTS = 'spatial intersects';
    public const REGEX = 'regex';

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

    public function isEquals(): bool
    {
        return $this->value == self::EQUALS;
    }

    public static function equals(): WhereOperator
    {
        return new WhereOperator(self::EQUALS);
    }

    public function isNotEquals(): bool
    {
        return $this->value == self::NOT_EQUALS;
    }

    public static function notEquals(): WhereOperator
    {
        return new WhereOperator(self::NOT_EQUALS);
    }

    public function isGreaterThan(): bool
    {
        return $this->value == self::GREATER_THAN;
    }

    public static function greaterThan(): WhereOperator
    {
        return new WhereOperator(self::GREATER_THAN);
    }

    public function isGreaterThanOrEqual(): bool
    {
        return $this->value == self::GREATER_THAN_OR_EQUAL;
    }

    public static function greaterThanOrEqual(): WhereOperator
    {
        return new WhereOperator(self::GREATER_THAN_OR_EQUAL);
    }

    public function isLessThan(): bool
    {
        return $this->value == self::LESS_THAN;
    }

    public static function lessThan(): WhereOperator
    {
        return new WhereOperator(self::LESS_THAN);
    }

    public function isLessThanOrEqual(): bool
    {
        return $this->value == self::LESS_THAN_OR_EQUAL;
    }

    public static function lessThanOrEqual(): WhereOperator
    {
        return new WhereOperator(self::LESS_THAN_OR_EQUAL);
    }

    public function isIn(): bool
    {
        return $this->value == self::IN;
    }

    public static function in(): WhereOperator
    {
        return new WhereOperator(self::IN);
    }

    public function isAllIn(): bool
    {
        return $this->value == self::ALL_IN;
    }

    public static function allIn(): WhereOperator
    {
        return new WhereOperator(self::ALL_IN);
    }

    public function isBetween(): bool
    {
        return $this->value == self::BETWEEN;
    }

    public static function between(): WhereOperator
    {
        return new WhereOperator(self::BETWEEN);
    }

    public function isSearch(): bool
    {
        return $this->value == self::SEARCH;
    }

    public static function search(): WhereOperator
    {
        return new WhereOperator(self::SEARCH);
    }

    public function isLucene(): bool
    {
        return $this->value == self::LUCENE;
    }

    public static function lucene(): WhereOperator
    {
        return new WhereOperator(self::LUCENE);
    }

    public function isStartsWith(): bool
    {
        return $this->value == self::STARTS_WITH;
    }

    public static function startsWith(): WhereOperator
    {
        return new WhereOperator(self::STARTS_WITH);
    }

    public function isEndsWith(): bool
    {
        return $this->value == self::ENDS_WITH;
    }

    public static function endsWith(): WhereOperator
    {
        return new WhereOperator(self::ENDS_WITH);
    }

    public function isExists(): bool
    {
        return $this->value == self::EXISTS;
    }

    public static function exists(): WhereOperator
    {
        return new WhereOperator(self::EXISTS);
    }

    public function isSpatialWithin(): bool
    {
        return $this->value == self::SPATIAL_WITHIN;
    }

    public static function spatialWithin(): WhereOperator
    {
        return new WhereOperator(self::SPATIAL_WITHIN);
    }

    public function isSpatialContains(): bool
    {
        return $this->value == self::SPATIAL_CONTAINS;
    }

    public static function spatialContains(): WhereOperator
    {
        return new WhereOperator(self::SPATIAL_CONTAINS);
    }

    public function isSpatialDisjoint(): bool
    {
        return $this->value == self::SPATIAL_DISJOINT;
    }

    public static function spatialDisjoint(): WhereOperator
    {
        return new WhereOperator(self::SPATIAL_DISJOINT);
    }

    public function isSpatialIntersect(): bool
    {
        return $this->value == self::SPATIAL_INTERSECTS;
    }

    public static function spatialIntersect(): WhereOperator
    {
        return new WhereOperator(self::SPATIAL_INTERSECTS);
    }

    public function isRegex(): bool
    {
        return $this->value == self::REGEX;
    }

    public static function regex(): WhereOperator
    {
        return new WhereOperator(self::REGEX);
    }
}
