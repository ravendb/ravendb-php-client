<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Type\ValueObjectInterface;

class CompareExchangeValueState implements ValueObjectInterface
{
    public const NONE = 'None';
    public const CREATED = 'Created';
    public const DELETED = 'Deleted';
    public const MISSING = 'Missing';

    private string $value = '';

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

    public static function none(): CompareExchangeValueState
    {
        return new CompareExchangeValueState(self::NONE);
    }

    public function isCreated(): bool
    {
        return $this->value == self::CREATED;
    }

    public static function created(): CompareExchangeValueState
    {
        return new CompareExchangeValueState(self::CREATED);
    }

    public function isDeleted(): bool
    {
        return $this->value == self::DELETED;
    }

    public static function deleted(): CompareExchangeValueState
    {
        return new CompareExchangeValueState(self::DELETED);
    }

    public function isMissing(): bool
    {
        return $this->value == self::MISSING;
    }

    public static function missing(): CompareExchangeValueState
    {
        return new CompareExchangeValueState(self::MISSING);
    }
}
