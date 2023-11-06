<?php

namespace RavenDB\Http;

use RavenDB\Type\ValueObjectInterface;

class AggressiveCacheMode implements ValueObjectInterface
{
    public const TRACK_CHANGES = 'TRACK_CHANGES';
    public const DO_NOT_TRACK_CHANGES = 'DO_NOT_TRACK_CHANGES';

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

    public function isTrackChanges(): bool
    {
        return $this->value == self::TRACK_CHANGES;
    }

    public function isDoNotTrackChanges(): bool
    {
        return $this->value == self::DO_NOT_TRACK_CHANGES;
    }

    public static function trackChanges(): AggressiveCacheMode
    {
        return new AggressiveCacheMode(self::TRACK_CHANGES);
    }

    public static function doNotTrackChanges(): AggressiveCacheMode
    {
        return new AggressiveCacheMode(self::DO_NOT_TRACK_CHANGES);
    }
}
