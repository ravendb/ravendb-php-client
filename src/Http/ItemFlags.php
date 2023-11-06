<?php

namespace RavenDB\Http;

use RavenDB\Type\ValueObjectInterface;

class ItemFlags implements ValueObjectInterface
{
    public const NONE = 'NONE';
    public const NOT_FOUND = 'NOT_FOUND';
    public const AGGRESSIVELY_CACHED = 'AGGRESSIVELY_CACHED';

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

    public function isNotFound(): bool
    {
        return $this->value == self::NOT_FOUND;
    }

    public function isAggressivelyCached(): bool
    {
        return $this->value == self::AGGRESSIVELY_CACHED;
    }

    public static function none(): ItemFlags
    {
        return new ItemFlags(self::NONE);
    }

    public static function notFound(): ItemFlags
    {
        return new ItemFlags(self::NOT_FOUND);
    }

    public static function aggressivelyCached(): ItemFlags
    {
        return new ItemFlags(self::AGGRESSIVELY_CACHED);
    }

    public function isEqual(ItemFlags $flag): bool
    {
        return $this->value == $flag->getValue();
    }
}
