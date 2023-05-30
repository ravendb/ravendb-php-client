<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Type\ValueObjectInterface;

class EtlType implements ValueObjectInterface
{
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

    public function isRaven(): bool
    {
        return $this->value == self::RAVEN;
    }

    public static function raven(): EtlType
    {
        return new EtlType(self::RAVEN);
    }

    public function isSql(): bool
    {
        return $this->value == self::SQL;
    }

    public static function sql(): EtlType
    {
        return new EtlType(self::SQL);
    }

    public function isOlap(): bool
    {
        return $this->value == self::OLAP;
    }

    public static function olap(): EtlType
    {
        return new EtlType(self::OLAP);
    }
}
