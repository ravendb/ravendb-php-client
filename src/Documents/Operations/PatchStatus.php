<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Type\ValueObjectInterface;

class PatchStatus implements ValueObjectInterface
{
    public const DOCUMENT_DOES_NOT_EXIST = 'DocumentDoesNotExist';
    public const CREATED = 'Created';
    public const PATCHED = 'Patched';
    public const SKIPPED = 'Skipped';
    public const NOT_MODIFIED = 'NotModified';

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

    public function isDocumentDoesNotExist(): bool
    {
        return $this->value == self::DOCUMENT_DOES_NOT_EXIST;
    }

    public static function documentDoesNotExist(): PatchStatus
    {
        return new PatchStatus(self::DOCUMENT_DOES_NOT_EXIST);
    }

    public function isCreated(): bool
    {
        return $this->value == self::CREATED;
    }

    public static function created(): PatchStatus
    {
        return new PatchStatus(self::CREATED);
    }

    public function isPatched(): bool
    {
        return $this->value == self::PATCHED;
    }

    public static function patched(): PatchStatus
    {
        return new PatchStatus(self::PATCHED);
    }

    public function isSkipped(): bool
    {
        return $this->value == self::SKIPPED;
    }

    public static function skipped(): PatchStatus
    {
        return new PatchStatus(self::SKIPPED);
    }

    public function isNotModified(): bool
    {
        return $this->value == self::NOT_MODIFIED;
    }

    public static function notModified(): PatchStatus
    {
        return new PatchStatus(self::NOT_MODIFIED);
    }
}
