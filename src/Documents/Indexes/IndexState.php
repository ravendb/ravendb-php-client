<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class IndexState implements ValueObjectInterface
{
    private const NORMAL = 'Normal';
    private const DISABLED = 'Disabled';
    private const IDLE = 'Idle';
    private const ERROR = 'Error';

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

    public static function normal(): IndexState
    {
        return new IndexState(self::NORMAL);
    }

    public function isDisabled(): bool
    {
        return $this->value == self::DISABLED;
    }

    public static function disabled(): IndexState
    {
        return new IndexState(self::DISABLED);
    }

    public function isIdle(): bool
    {
        return $this->value == self::IDLE;
    }

    public static function idle(): IndexState
    {
        return new IndexState(self::IDLE);
    }

    public function isError(): bool
    {
        return $this->value == self::ERROR;
    }

    public static function error(): IndexState
    {
        return new IndexState(self::ERROR);
    }
}
