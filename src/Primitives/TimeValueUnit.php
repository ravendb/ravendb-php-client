<?php

namespace RavenDB\Primitives;

use RavenDB\Type\ValueObjectInterface;

class TimeValueUnit implements ValueObjectInterface
{
    public const NONE = 'None';
    public const SECOND = 'Second';
    public const MONTH = 'Month';

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

    public function isSecond(): bool
    {
        return $this->value == self::SECOND;
    }

    public function isMonth(): bool
    {
        return $this->value == self::MONTH;
    }

    public static function none(): TimeValueUnit
    {
        return new TimeValueUnit(self::NONE);
    }

    public static function second(): TimeValueUnit
    {
        return new TimeValueUnit(self::SECOND);
    }

    public static function month(): TimeValueUnit
    {
        return new TimeValueUnit(self::MONTH);
    }
}
