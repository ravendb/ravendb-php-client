<?php

namespace RavenDB\Documents\Operations\Replication;

use RavenDB\Type\ValueObjectInterface;

class PreventDeletionsMode implements ValueObjectInterface
{
    private const NONE = 'None';
    private const PREVENT_SINK_TO_HUB_DELETIONS = 'PreventSinkToHubDeletions';

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

    public static function none(): PreventDeletionsMode
    {
        return new PreventDeletionsMode(self::NONE);
    }

    public function isPreventSinkToHubDeletions(): bool
    {
        return $this->value == self::PREVENT_SINK_TO_HUB_DELETIONS;
    }

    public static function preventSinkToHubDeletions(): PreventDeletionsMode
    {
        return new PreventDeletionsMode(self::PREVENT_SINK_TO_HUB_DELETIONS);
    }
}
