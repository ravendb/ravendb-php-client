<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\ValueObjectInterface;

class OrderingType implements ValueObjectInterface
{
    public const STRING = 'string';
    public const LONG = 'long';
    public const DOUBLE = 'double';
    public const ALPHA_NUMERIC = 'alphaNumeric';

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

    public function isString(): bool
    {
        return $this->value == self::STRING;
    }

    public static function string(): OrderingType
    {
        return new OrderingType(self::STRING);
    }

    public function isLong(): bool
    {
        return $this->value == self::LONG;
    }

    public static function long(): OrderingType
    {
        return new OrderingType(self::LONG);
    }

    public function isDouble(): bool
    {
        return $this->value == self::DOUBLE;
    }

    public static function double(): OrderingType
    {
        return new OrderingType(self::DOUBLE);
    }

    public function isAlphaNumeric(): bool
    {
        return $this->value == self::ALPHA_NUMERIC;
    }

    public static function alphaNumeric(): OrderingType
    {
        return new OrderingType(self::ALPHA_NUMERIC);
    }
}
