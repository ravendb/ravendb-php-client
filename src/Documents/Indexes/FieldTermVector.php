<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class FieldTermVector implements ValueObjectInterface
{
    private const NO = 'No';
    private const YES = 'Yes';
    private const WITH_POSITIONS = 'WithPositions';
    private const WITH_OFFSETS = 'WithOffsets';
    private const WITH_POSITIONS_AND_OFFSETS = 'WithPositionsAndOffsets';

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

    public function isNo(): bool
    {
        return $this->value == self::NO;
    }

    /**
     * Do not store term vectors
     */
    public static function no(): FieldTermVector
    {
        return new FieldTermVector(self::NO);
    }

    public function isYes(): bool
    {
        return $this->value == self::YES;
    }

    /**
     * Store the term vectors of each document. A term vector is a list of the document's
     * terms and their number of occurrences in that document.
     */
    public static function yes(): FieldTermVector
    {
        return new FieldTermVector(self::YES);
    }

    public function isWithPositions(): bool
    {
        return $this->value == self::WITH_POSITIONS;
    }

    /**
     * Store the term vector + token position information
     */
    public static function withPositions(): FieldTermVector
    {
        return new FieldTermVector(self::WITH_POSITIONS);
    }

    public function isWithOffsets(): bool
    {
        return $this->value == self::WITH_OFFSETS;
    }

    /**
     * Store the term vector + Token offset information
     */
    public static function withOffsets(): FieldTermVector
    {
        return new FieldTermVector(self::WITH_OFFSETS);
    }

    public function isWithPositionsAndOffsets(): bool
    {
        return $this->value == self::WITH_POSITIONS_AND_OFFSETS;
    }

    /**
     * Store the term vector + Token position and offset information
     */
    public static function withPositionsAndOffsets(): FieldTermVector
    {
        return new FieldTermVector(self::WITH_POSITIONS_AND_OFFSETS);
    }
}
