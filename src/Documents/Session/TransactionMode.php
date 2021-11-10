<?php

namespace RavenDB\Documents\Session;

use RavenDB\Exceptions\InvalidValueException;

class TransactionMode
{
    const NAME = 'TransactionMode';

    const SINGLE_NODE = 'SINGLE_NODE';
    const CLUSTER_WIDE = 'CLUSTER_WIDE';

    private string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    /**
     * @throws InvalidValueException
     */
    public function setValue(string $value)
    {
        $upperCaseValue = strtoupper($value);
        $this->validate($upperCaseValue);
        $this->value = $upperCaseValue;
    }

    public function getValue(): string
    {
        return $this->getValue();
    }

    public function isSingleNode(): bool
    {
        return $this->value === self::SINGLE_NODE;
    }

    public function isClusterWide(): bool
    {
        return $this->value === self::CLUSTER_WIDE;
    }

    public static function allValues(): array
    {
        return [
            self::SINGLE_NODE,
            self::CLUSTER_WIDE
        ];
    }

    public static function singleNode(): TransactionMode
    {
        return new TransactionMode(TransactionMode::SINGLE_NODE);
    }

    public static function clusterWide(): TransactionMode
    {
        return new TransactionMode(TransactionMode::CLUSTER_WIDE);
    }

    /**
     * @throws InvalidValueException
     */
    private function validate(string $value)
    {
        if (!in_array($value, self::allValues())) {
            throw new InvalidValueException(self::NAME, $value);
        }
    }

    public function isEqual(TransactionMode $mode): bool
    {
        return $this->value == $mode->value;
    }
}
