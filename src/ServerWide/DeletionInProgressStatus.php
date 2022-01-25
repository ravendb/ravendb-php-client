<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\ValueObjectInterface;

// !status = DONE
class DeletionInProgressStatus implements ValueObjectInterface
{
    public const NO = 'NO';
    public const SOFT_DELETE = 'SOFT_DELETE';
    public const HARD_DELETE = 'HARD_DELETE';

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

    public function isNo(): bool
    {
        return $this->value == self::NO;
    }

    public function isSoftDelete(): bool
    {
        return $this->value == self::SOFT_DELETE;
    }

    public function isHardDelete(): bool
    {
        return $this->value == self::HARD_DELETE;
    }

    public static function no(): DeletionInProgressStatus
    {
        return new DeletionInProgressStatus(self::NO);
    }

    public static function softDelete(): DeletionInProgressStatus
    {
        return new DeletionInProgressStatus(self::SOFT_DELETE);
    }

    public static function hardDelete(): DeletionInProgressStatus
    {
        return new DeletionInProgressStatus(self::HARD_DELETE);
    }


}
