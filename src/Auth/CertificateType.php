<?php

namespace RavenDB\Auth;

use RavenDB\Type\ValueObjectInterface;

class CertificateType implements ValueObjectInterface
{
    private const PEM = 'pem';
    private const PFX = 'pfx';

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

    public function isPem(): bool
    {
        return $this->value == self::PEM;
    }

    public function isPfx(): bool
    {
        return $this->value == self::PFX;
    }

    public static function pem(): CertificateType
    {
        return new CertificateType(self::PEM);
    }

    public static function pfx(): CertificateType
    {
        return new CertificateType(self::PFX);
    }
}
