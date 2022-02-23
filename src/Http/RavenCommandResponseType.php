<?php

namespace RavenDB\Http;

use RavenDB\Type\ValueObjectInterface;

// !status = DONE
class RavenCommandResponseType implements ValueObjectInterface
{
    public const EMPTY = 'EMPTY';
    public const OBJECT = 'OBJECT';
    public const RAW = 'RAW';

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

    public function isEmpty(): bool
    {
        return $this->value == self::EMPTY;
    }

    public function isObject(): bool
    {
        return $this->value == self::OBJECT;
    }

    public function isRaw(): bool
    {
        return $this->value == self::RAW;
    }

    public static function empty(): RavenCommandResponseType
    {
        return new RavenCommandResponseType(self::EMPTY);
    }

    public static function object(): RavenCommandResponseType
    {
        return new RavenCommandResponseType(self::OBJECT);
    }

    public static function raw(): RavenCommandResponseType
    {
        return new RavenCommandResponseType(self::RAW);
    }
}
