<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\ValueObjectInterface;

class DatabaseStateStatus implements ValueObjectInterface
{
    public const NORMAL = 'Normal';
    public const RESTORE_IN_PROGRESS = 'RESTORE_IN_PROGRESS';

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

    public function isNormal(): bool
    {
        return $this->value == self::NORMAL;
    }

    public function isRestoreInProgress(): bool
    {
        return $this->value == self::RESTORE_IN_PROGRESS;
    }

    public static function normal(): DatabaseStateStatus
    {
        return new DatabaseStateStatus(self::NORMAL);
    }

    public static function restoreInProgress(): DatabaseStateStatus
    {
        return new DatabaseStateStatus(self::RESTORE_IN_PROGRESS);
    }
}
