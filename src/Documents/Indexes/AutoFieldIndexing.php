<?php

namespace RavenDB\Documents\Indexes;
use RavenDB\Type\ValueObjectInterface;

class AutoFieldIndexing implements ValueObjectInterface
{
    private const NO = 'No';
    private const SEARCH = 'Search';
    private const EXACT = 'Exact';
    private const HIGHLIGHTING = 'Highlighting';
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

    public static function no(): AutoFieldIndexing
    {
        return new AutoFieldIndexing(self::NO);
    }

    public function isSearch(): bool
    {
        return $this->value == self::SEARCH;
    }

    public static function search(): AutoFieldIndexing
    {
        return new AutoFieldIndexing(self::SEARCH);
    }

    public function isExact(): bool
    {
        return $this->value == self::EXACT;
    }

    public static function exact(): AutoFieldIndexing
    {
        return new AutoFieldIndexing(self::EXACT);
    }

    public function isHighlighting(): bool
    {
        return $this->value == self::HIGHLIGHTING;
    }

    public static function highlighting(): AutoFieldIndexing
    {
        return new AutoFieldIndexing(self::HIGHLIGHTING);
    }

    public function isDefault(): bool
    {
        return $this->value == self::DEFAULT;
    }

    public static function default(): AutoFieldIndexing
    {
        return new AutoFieldIndexing(self::DEFAULT);
    }
}
