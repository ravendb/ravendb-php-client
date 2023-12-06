<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class FieldStorage implements ValueObjectInterface
{
    private const YES = 'Yes';
    private const NO = 'No';

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

    public function isYes(): bool
    {
        return $this->value == self::YES;
    }

    /**
     *  Store the original field value in the index. This is useful for short texts like a document's title which should be displayed with the results.
     *  The value is stored in its original form, i.e. no analyzer is used before it is stored.
     */
    public static function yes(): FieldStorage
    {
        return new FieldStorage(self::YES);
    }

    public function isNo(): bool
    {
        return $this->value == self::NO;
    }

    /**
     * Do not store the field value in the index.
     */
    public static function no(): FieldStorage
    {
        return new FieldStorage(self::NO);
    }
}
