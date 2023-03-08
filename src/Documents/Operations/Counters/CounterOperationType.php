<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Type\ValueObjectInterface;

class CounterOperationType implements ValueObjectInterface
{
    private const NONE = 'None';
    private const INCREMENT = 'Increment';
    private const DELETE = 'Delete';
    private const GET = 'Get';
    private const PUT = 'Put';

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

    public static function none(): CounterOperationType
    {
        return new CounterOperationType(self::NONE);
    }

    public function isIncrement(): bool
    {
        return $this->value == self::INCREMENT;
    }

    public static function increment(): CounterOperationType
    {
        return new CounterOperationType(self::INCREMENT);
    }

    public function isDelete(): bool
    {
        return $this->value == self::DELETE;
    }

    public static function delete(): CounterOperationType
    {
        return new CounterOperationType(self::DELETE);
    }

    public function isGet(): bool
    {
        return $this->value == self::GET;
    }

    public static function get(): CounterOperationType
    {
        return new CounterOperationType(self::GET);
    }

    public function isPut(): bool
    {
        return $this->value == self::PUT;
    }

    public static function put(): CounterOperationType
    {
        return new CounterOperationType(self::PUT);
    }

    public function equals(CounterOperationType $that): bool
    {
        return $this->value == $that->value;
    }
}
