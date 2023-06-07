<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Type\ValueObjectInterface;

class LazyRevisionOperationMode implements ValueObjectInterface
{
    public const SINGLE = 'Single';
    public const MULTI = 'Multi';
    public const MAP = 'Map';
    public const LIST_OF_METADATA = 'ListOfMetadata';

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

    public function isSingle(): bool
    {
        return $this->value == self::SINGLE;
    }

    public static function single(): LazyRevisionOperationMode
    {
        return new LazyRevisionOperationMode(self::SINGLE);
    }

    public function isMulti(): bool
    {
        return $this->value == self::MULTI;
    }

    public static function multi(): LazyRevisionOperationMode
    {
        return new LazyRevisionOperationMode(self::MULTI);
    }

    public function isMap(): bool
    {
        return $this->value == self::MAP;
    }

    public static function map(): LazyRevisionOperationMode
    {
        return new LazyRevisionOperationMode(self::MAP);
    }

    public function isListOfMetadata(): bool
    {
        return $this->value == self::LIST_OF_METADATA;
    }

    public static function listOfMetadata(): LazyRevisionOperationMode
    {
        return new LazyRevisionOperationMode(self::LIST_OF_METADATA);
    }
}
