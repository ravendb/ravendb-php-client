<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class SearchEngineType implements ValueObjectInterface
{
    private const NONE = 'None';
    private const CORAX = 'Corax';
    private const LUCENE = 'Lucene';

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

    public static function none(): SearchEngineType
    {
        return new SearchEngineType(self::NONE);
    }

    public function isCorax(): bool
    {
        return $this->value == self::CORAX;
    }

    public static function corax(): SearchEngineType
    {
        return new SearchEngineType(self::CORAX);
    }

    public function isLucene(): bool
    {
        return $this->value == self::LUCENE;
    }

    public static function lucene(): SearchEngineType
    {
        return new SearchEngineType(self::LUCENE);
    }
}
