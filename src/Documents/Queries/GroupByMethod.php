<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\ValueObjectInterface;

class GroupByMethod implements ValueObjectInterface
{
    private const NONE = 'none';
    private const ARRAY = 'array';

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

    public static function none(): GroupByMethod
    {
        return new GroupByMethod(self::NONE);
    }

    public function isArray(): bool
    {
        return $this->value == self::ARRAY;
    }

    public static function array(): GroupByMethod
    {
        return new GroupByMethod(self::ARRAY);
    }
}
