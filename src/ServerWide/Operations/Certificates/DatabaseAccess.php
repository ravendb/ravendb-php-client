<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Type\ValueObjectInterface;

// !status = DONE
class DatabaseAccess implements ValueObjectInterface
{
    public const READ_WRITE = 'ReadWrite';
    public const ADMIN = 'Admin';
    public const READ = 'Read';

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

    public function isReadWrite(): bool
    {
        return $this->value == self::READ_WRITE;
    }

    public function isAdmin(): bool
    {
        return $this->value == self::ADMIN;
    }

    public function isRead(): bool
    {
        return $this->value == self::READ;
    }

    public static function readWrite(): DatabaseAccess
    {
        return new DatabaseAccess(self::READ_WRITE);
    }

    public static function admin(): DatabaseAccess
    {
        return new DatabaseAccess(self::ADMIN);
    }

    public static function read(): DatabaseAccess
    {
        return new DatabaseAccess(self::READ);
    }
}
