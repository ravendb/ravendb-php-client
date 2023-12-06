<?php

namespace RavenDB\Http;

use RavenDB\Type\ValueObjectInterface;

class ResponseDisposeHandling implements ValueObjectInterface
{
    public const MANUALLY = 'MANUALLY';
    public const AUTOMATIC = 'AUTOMATIC';

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

    public function isManually(): bool
    {
        return $this->value == self::MANUALLY;
    }

    public function isAutomatic(): bool
    {
        return $this->value == self::AUTOMATIC;
    }

    public static function manually(): ResponseDisposeHandling
    {
        return new ResponseDisposeHandling(self::MANUALLY);
    }

    public static function automatic(): ResponseDisposeHandling
    {
        return new ResponseDisposeHandling(self::AUTOMATIC);
    }
}
