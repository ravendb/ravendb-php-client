<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\ValueObjectInterface;

class QueryOperator implements ValueObjectInterface
{
    private const AND = 'and';
    private const OR = 'or';

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

    public function isAnd(): bool
    {
        return $this->value == self::AND;
    }

    public function isOr(): bool
    {
        return $this->value == self::OR;
    }

    public static function and(): QueryOperator
    {
        return new QueryOperator(self::AND);
    }

    public static function or(): QueryOperator
    {
        return new QueryOperator(self::OR);
    }
}
