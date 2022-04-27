<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class IndexDeploymentMode implements ValueObjectInterface
{
    private const PARALLEL = 'Parallel';
    private const ROLLING = 'Rolling';

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

    public function isParallel(): bool
    {
        return $this->value == self::PARALLEL;
    }

    public static function parallel(): IndexDeploymentMode
    {
        return new IndexDeploymentMode(self::PARALLEL);
    }

    public function isRolling(): bool
    {
        return $this->value == self::ROLLING;
    }

    public static function rolling(): IndexDeploymentMode
    {
        return new IndexDeploymentMode(self::ROLLING);
    }
}
