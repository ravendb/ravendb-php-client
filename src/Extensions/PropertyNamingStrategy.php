<?php

namespace RavenDB\Extensions;

use RavenDB\Type\ValueObjectInterface;

class PropertyNamingStrategy implements ValueObjectInterface
{
    private const none = 'None';
    private const dotNetNamingStrategy = 'DotNetNamingStrategy';

    private const default = self::none;

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isDotNetNamingStrategy(): bool
    {
        return $this->value == self::dotNetNamingStrategy;
    }

    public function isNone(): bool
    {
        return $this->value == self::none;
    }


    public static function dotNetNamingStrategy(): PropertyNamingStrategy
    {
        return new PropertyNamingStrategy(self::dotNetNamingStrategy);
    }

    public static function none(): PropertyNamingStrategy
    {
        return new PropertyNamingStrategy(self::none);
    }

}
