<?php

namespace RavenDB\Http;

use RavenDB\Type\ValueObjectInterface;

class ReadBalanceBehavior implements ValueObjectInterface
{
    public const NONE = 'None';
    public const ROUND_ROBIN = 'RoundRobin';
    public const FASTEST_NODE = 'FastestNode';

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

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public function isRoundRobin(): bool
    {
        return $this->value == self::ROUND_ROBIN;
    }

    public function isFastestNode(): bool
    {
        return $this->value == self::FASTEST_NODE;
    }

    public static function none(): ReadBalanceBehavior
    {
        return new ReadBalanceBehavior(self::NONE);
    }

    public static function roundRobin(): ReadBalanceBehavior
    {
        return new ReadBalanceBehavior(self::ROUND_ROBIN);
    }

    public static function fastestNode(): ReadBalanceBehavior
    {
        return new ReadBalanceBehavior(self::FASTEST_NODE);
    }
}
