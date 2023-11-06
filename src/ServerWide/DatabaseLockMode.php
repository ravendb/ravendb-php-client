<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\ValueObjectInterface;

class DatabaseLockMode implements ValueObjectInterface
{
    public const UNLOCK = 'Unlock';
    public const PREVENT_DELETES_IGNORE = 'PreventDeletesIgnore';
    public const PREVENT_DELETES_ERROR = 'PreventDeletesError';

    private string $value = '';

    public function __construct(string $value = '')
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

    public function isUnlock(): bool
    {
        return $this->value == self::UNLOCK;
    }

    public function isPreventDeletesIgnore(): bool
    {
        return $this->value == self::PREVENT_DELETES_IGNORE;
    }

    public function isPreventDeletesError(): bool
    {
        return $this->value == self::PREVENT_DELETES_ERROR;
    }

    public static function none(): DatabaseLockMode
    {
        return new DatabaseLockMode(self::UNLOCK);
    }

    public static function unlock(): DatabaseLockMode
    {
        return new DatabaseLockMode(self::UNLOCK);
    }

    public static function preventDeletesIgnore(): DatabaseLockMode
    {
        return new DatabaseLockMode(self::PREVENT_DELETES_IGNORE);
    }

    public static function preventDeletesError(): DatabaseLockMode
    {
        return new DatabaseLockMode(self::PREVENT_DELETES_ERROR);
    }
}
