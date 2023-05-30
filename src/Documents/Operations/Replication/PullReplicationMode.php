<?php

namespace RavenDB\Documents\Operations\Replication;

use RavenDB\Type\ValueObjectInterface;

class PullReplicationMode implements ValueObjectInterface
{
    private const NONE = 'None';
    private const HUB_TO_SINK = 'HubToSink';
    private const SINK_TO_HUB = 'SinkToHub';

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

    public static function none(): PullReplicationMode
    {
        return new PullReplicationMode(self::NONE);
    }

    public function isHubToSink(): bool
    {
        return $this->value == self::HUB_TO_SINK;
    }

    public static function hubToSink(): PullReplicationMode
    {
        return new PullReplicationMode(self::HUB_TO_SINK);
    }

    public function isSinkToHub(): bool
    {
        return $this->value == self::SINK_TO_HUB;
    }

    public static function sinkToHub(): PullReplicationMode
    {
        return new PullReplicationMode(self::SINK_TO_HUB);
    }
}
