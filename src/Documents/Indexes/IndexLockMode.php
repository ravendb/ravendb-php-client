<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class IndexLockMode implements ValueObjectInterface
{
    private const UNLOCK = 'Unlock';
    private const LOCKED_IGNORE = 'LockedIgnore';
    private const LOCKED_ERROR = 'LockedError';

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

    public function isUnlock(): bool
    {
        return $this->value == self::UNLOCK;
    }

    public static function unlock(): IndexLockMode
    {
        return new IndexLockMode(self::UNLOCK);
    }

    public function isLockedIgnore(): bool
    {
        return $this->value == self::LOCKED_IGNORE;
    }

    public static function lockedIgnore(): IndexLockMode
    {
        return new IndexLockMode(self::LOCKED_IGNORE);
    }

    public function isLockedError(): bool
    {
        return $this->value == self::LOCKED_ERROR;
    }

    public static function lockedError(): IndexLockMode
    {
        return new IndexLockMode(self::LOCKED_ERROR);
    }
}
