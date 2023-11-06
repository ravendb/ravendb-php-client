<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class FieldIndexing implements ValueObjectInterface
{
    private const NO = 'No';
    private const SEARCH = 'Search';
    private const EXACT = 'Exact';
    private const HIGHLIGHTING = 'Highlightings';
    private const DEFAULT = 'Default';

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
     * Do not index the field value. This field can thus not be searched, but one can still access its contents provided it is stored.
     */
    public static function no(): FieldIndexing
    {
        return new FieldIndexing(self::NO);
    }

    public function isSearch(): bool
    {
        return $this->value == self::SEARCH;
    }

    /**
     * Index the tokens produced by running the field's value through an Analyzer. This is useful for common text.
     */
    public static function search(): FieldIndexing
    {
        return new FieldIndexing(self::SEARCH);
    }

    public function isExact(): bool
    {
        return $this->value == self::EXACT;
    }

    /**
     * Index the field's value without using an Analyzer, so it can be searched.  As no analyzer is used the
     * value will be stored as a single term. This is useful for unique Ids like product numbers.
     */
    public static function exact(): FieldIndexing
    {
        return new FieldIndexing(self::EXACT);
    }

    public function isHighlighting(): bool
    {
        return $this->value == self::HIGHLIGHTING;
    }

    /**
     * Index the tokens produced by running the field's value through an Analyzer (same as Search),
     * store them in index and track term vector positions and offsets. This is mandatory when highlighting is used.
     */
    public static function highlighting(): FieldIndexing
    {
        return new FieldIndexing(self::HIGHLIGHTING);
    }

    public function isDefault(): bool
    {
        return $this->value == self::DEFAULT;
    }

    /**
     *  Index this field using the default internal analyzer: LowerCaseKeywordAnalyzer
     */
    public static function default(): FieldIndexing
    {
        return new FieldIndexing(self::DEFAULT);
    }
}
