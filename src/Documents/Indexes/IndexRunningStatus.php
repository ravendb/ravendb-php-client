<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class IndexRunningStatus implements ValueObjectInterface
{
    private const RUNNING = 'Running';
    private const PAUSED = 'Paused';
    private const DISABLED = 'Disabled';
    private const PENDING = 'Pending';

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

    public function isRunning(): bool
    {
        return $this->value == self::RUNNING;
    }

    public static function running(): IndexRunningStatus
    {
        return new IndexRunningStatus(self::RUNNING);
    }

    public function isPaused(): bool
    {
        return $this->value == self::PAUSED;
    }

    public static function paused(): IndexRunningStatus
    {
        return new IndexRunningStatus(self::PAUSED);
    }

    public function isDisabled(): bool
    {
        return $this->value == self::DISABLED;
    }

    public static function disabled(): IndexRunningStatus
    {
        return new IndexRunningStatus(self::DISABLED);
    }

    public function isPending(): bool
    {
        return $this->value == self::PENDING;
    }

    public static function pending(): IndexRunningStatus
    {
        return new IndexRunningStatus(self::PENDING);
    }
}
