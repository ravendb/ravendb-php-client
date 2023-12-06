<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class RollingIndexState implements ValueObjectInterface
{
    public const PENDING = 'PENDING';
    public const RUNNING = 'RUNNING';
    public const DONE = 'DONE';

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

    public function isPending(): bool
    {
        return $this->value == self::PENDING;
    }

    public function isRunning(): bool
    {
        return $this->value == self::RUNNING;
    }

    public function isDone(): bool
    {
        return $this->value == self::DONE;
    }

    public static function pending(): RollingIndexState
    {
        return new RollingIndexState(self::PENDING);
    }

    public static function running(): RollingIndexState
    {
        return new RollingIndexState(self::RUNNING);
    }

    public static function done(): RollingIndexState
    {
        return new RollingIndexState(self::DONE);
    }
}
