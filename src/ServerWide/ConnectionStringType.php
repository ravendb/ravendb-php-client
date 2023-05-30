<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\ValueObjectInterface;

class ConnectionStringType implements ValueObjectInterface
{
    private const NONE = 'None';
    private const RAVEN = 'Raven';
    private const SQL = 'Sql';
    private const OLAP = 'Olap';

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

    public static function none(): ConnectionStringType
    {
        return new ConnectionStringType(self::NONE);
    }

    public function isRaven(): bool
    {
        return $this->value == self::RAVEN;
    }

    public static function raven(): ConnectionStringType
    {
        return new ConnectionStringType(self::RAVEN);
    }

    public function isSql(): bool
    {
        return $this->value == self::SQL;
    }

    public static function sql(): ConnectionStringType
    {
        return new ConnectionStringType(self::SQL);
    }

    public function isOlap(): bool
    {
        return $this->value == self::OLAP;
    }

    public static function olap(): ConnectionStringType
    {
        return new ConnectionStringType(self::OLAP);
    }
}
