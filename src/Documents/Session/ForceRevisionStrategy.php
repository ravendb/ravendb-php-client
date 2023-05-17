<?php

namespace RavenDB\Documents\Session;

class ForceRevisionStrategy
{
    const NAME = 'ForceRevisionStrategy';

    const NONE = 'None';
    const BEFORE = 'Before';

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public function isBefore(): bool
    {
        return $this->value == self::BEFORE;
    }

    static public function None(): ForceRevisionStrategy
    {
        return new self(self::NONE);
    }

    static public function Before(): ForceRevisionStrategy
    {
        return new self(self::BEFORE);
    }
}
