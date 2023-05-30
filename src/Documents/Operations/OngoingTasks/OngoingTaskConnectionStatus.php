<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Type\ValueObjectInterface;

class OngoingTaskConnectionStatus implements ValueObjectInterface
{
    private const NONE = 'None';
    private const ACTIVE = 'Active';
    private const NOT_ACTIVE = 'NotActive';
    private const RECONNECT = 'Reconnect';
    private const NOT_ON_THIS_NODE = 'NotOnThisNode';

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

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public static function none(): OngoingTaskConnectionStatus
    {
        return new OngoingTaskConnectionStatus(self::NONE);
    }

    public function isActive(): bool
    {
        return $this->value == self::ACTIVE;
    }

    public static function active(): OngoingTaskConnectionStatus
    {
        return new OngoingTaskConnectionStatus(self::ACTIVE);
    }

    public function isNotActive(): bool
    {
        return $this->value == self::NOT_ACTIVE;
    }

    public static function notActive(): OngoingTaskConnectionStatus
    {
        return new OngoingTaskConnectionStatus(self::NOT_ACTIVE);
    }

    public function isReconnect(): bool
    {
        return $this->value == self::RECONNECT;
    }

    public static function reconnect(): OngoingTaskConnectionStatus
    {
        return new OngoingTaskConnectionStatus(self::RECONNECT);
    }

    public function isNotOnThisNode(): bool
    {
        return $this->value == self::NOT_ON_THIS_NODE;
    }

    public static function notOnThisNode(): OngoingTaskConnectionStatus
    {
        return new OngoingTaskConnectionStatus(self::NOT_ON_THIS_NODE);
    }
}
