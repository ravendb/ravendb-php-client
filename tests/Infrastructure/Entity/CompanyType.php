<?php

namespace tests\RavenDB\Infrastructure\Entity;

use RavenDB\Type\ValueObjectInterface;

// !status: DONE
class CompanyType implements ValueObjectInterface
{
    private const PUBLIC = 'public';
    private const PRIVATE = 'private';

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

    public function isPublic(): bool
    {
        return $this->value == self::PUBLIC;
    }

    public static function public(): CompanyType
    {
        return new CompanyType(self::PUBLIC);
    }

    public function isPrivate(): bool
    {
        return $this->value == self::PRIVATE;
    }

    public static function private(): CompanyType
    {
        return new CompanyType(self::PRIVATE);
    }
}
