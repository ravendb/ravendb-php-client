<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Type\ValueObjectInterface;

class OngoingTaskState implements ValueObjectInterface
{
    private const ENABLED = 'Enabled';
    private const DISABLED = 'Disabled';
    private const PARTIALLY_ENABLED = 'PartiallyEnabled';

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

    public function isEnabled(): bool
    {
        return $this->value == self::ENABLED;
    }

    public static function enabled(): OngoingTaskState
    {
        return new OngoingTaskState(self::ENABLED);
    }

    public function isDisabled(): bool
    {
        return $this->value == self::DISABLED;
    }

    public static function disabled(): OngoingTaskState
    {
        return new OngoingTaskState(self::DISABLED);
    }

    public function isPartiallyEnabled(): bool
    {
        return $this->value == self::PARTIALLY_ENABLED;
    }

    public static function partiallyEnabled(): OngoingTaskState
    {
        return new OngoingTaskState(self::PARTIALLY_ENABLED);
    }
}
