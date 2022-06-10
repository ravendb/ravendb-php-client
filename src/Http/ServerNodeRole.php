<?php

namespace RavenDB\Http;

use RavenDB\Type\ValueObjectInterface;

class ServerNodeRole implements ValueObjectInterface
{
    public const NONE = 'None';
    public const PROMOTABLE = 'Promotable';
    public const MEMBER = 'Member';
    public const REHAB = 'Rehab';

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->value;
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

    public function isPromotable(): bool
    {
        return $this->value == self::PROMOTABLE;
    }

    public function isMember(): bool
    {
        return $this->value == self::MEMBER;
    }

    public function isRehab(): bool
    {
        return $this->value == self::REHAB;
    }

    public static function none(): ServerNodeRole
    {
        return new ServerNodeRole(self::NONE);
    }

    public static function promotable(): ServerNodeRole
    {
        return new ServerNodeRole(self::PROMOTABLE);
    }

    public static function member(): ServerNodeRole
    {
        return new ServerNodeRole(self::MEMBER);
    }

    public static function rehab(): ServerNodeRole
    {
        return new ServerNodeRole(self::REHAB);
    }
}
