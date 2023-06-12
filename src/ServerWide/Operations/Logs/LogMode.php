<?php

namespace RavenDB\ServerWide\Operations\Logs;

use RavenDB\Type\ValueObjectInterface;

class LogMode implements ValueObjectInterface
{
    public const NONE = 'None';
    public const OPERATIONS = 'Operations';
    public const INFORMATION = 'Information';

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

    public static function none(): LogMode
    {
        return new LogMode(self::NONE);
    }

    public function isOperations(): bool
    {
        return $this->value == self::OPERATIONS;
    }

    public static function operations(): LogMode
    {
        return new LogMode(self::OPERATIONS);
    }

    public function isInformation(): bool
    {
        return $this->value == self::INFORMATION;
    }

    public static function information(): LogMode
    {
        return new LogMode(self::INFORMATION);
    }
}
