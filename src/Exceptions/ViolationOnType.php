<?php

namespace RavenDB\Exceptions;

use RavenDB\Type\ValueObjectInterface;

class ViolationOnType implements ValueObjectInterface
{
    private const DOCUMENT = 'Document';
    private const COMPARE_EXCHANGE = 'CompareExchange';

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

    public function isDocument(): bool
    {
        return $this->value == self::DOCUMENT;
    }

    public static function document(): ViolationOnType
    {
        return new ViolationOnType(self::DOCUMENT);
    }

    public function isCompareExchange(): bool
    {
        return $this->value == self::COMPARE_EXCHANGE;
    }

    public static function compareExchange(): ViolationOnType
    {
        return new ViolationOnType(self::COMPARE_EXCHANGE);
    }
}
