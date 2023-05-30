<?php

namespace RavenDB\Documents\Operations\Backups;

use RavenDB\Type\ValueObjectInterface;

class BackupType implements ValueObjectInterface
{
    private const BACKUP = 'Backup';
    private const SNAPSHOT = 'Snapshot';

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

    public function isBackup(): bool
    {
        return $this->value == self::BACKUP;
    }

    public static function backup(): BackupType
    {
        return new BackupType(self::BACKUP);
    }

    public function isSnapshot(): bool
    {
        return $this->value == self::SNAPSHOT;
    }

    public static function snapshot(): BackupType
    {
        return new BackupType(self::SNAPSHOT);
    }
}
