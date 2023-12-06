<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\ValueObjectInterface;

class MethodsType implements ValueObjectInterface
{
    public const CMP_X_CHG = 'cmp x chg';

    public string $value;

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

    public function isCmpXChg(): bool
    {
        return $this->value == self::CMP_X_CHG;
    }

    public static function cmpXChg(): MethodsType
    {
        return new MethodsType(self::CMP_X_CHG);
    }
}
