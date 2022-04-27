<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class IndexType implements ValueObjectInterface
{
    private const NONE = 'None';
    private const AUTO_MAP = 'AutoMap';
    private const AUTO_MAP_REDUCE = 'AutoMapReduce';
    private const MAP = 'Map';
    private const MAP_REDUCE = 'MapReduce';
    private const FAULTY = 'Faulty';
    private const JAVA_SCRIPT_MAP = 'JavaScriptMap';
    private const JAVA_SCRIPT_MAP_REDUCE = 'JavaScriptMapReduce';

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

    public static function none(): IndexType
    {
        return new IndexType(self::NONE);
    }

    public function isAutoMap(): bool
    {
        return $this->value == self::AUTO_MAP;
    }

    public static function autoMap(): IndexType
    {
        return new IndexType(self::AUTO_MAP);
    }

    public function isAutoMapReduce(): bool
    {
        return $this->value == self::AUTO_MAP_REDUCE;
    }

    public static function autoMapReduce(): IndexType
    {
        return new IndexType(self::AUTO_MAP_REDUCE);
    }

    public function isMap(): bool
    {
        return $this->value == self::MAP;
    }

    public static function map(): IndexType
    {
        return new IndexType(self::MAP);
    }

    public function isMapReduce(): bool
    {
        return $this->value == self::MAP_REDUCE;
    }

    public static function mapReduce(): IndexType
    {
        return new IndexType(self::MAP_REDUCE);
    }

    public function isFaulty(): bool
    {
        return $this->value == self::FAULTY;
    }

    public static function faulty(): IndexType
    {
        return new IndexType(self::FAULTY);
    }

    public function isJavaScriptMap(): bool
    {
        return $this->value == self::JAVA_SCRIPT_MAP;
    }

    public static function javaScriptMap(): IndexType
    {
        return new IndexType(self::JAVA_SCRIPT_MAP);
    }

    public function isJavaScriptMapReduce(): bool
    {
        return $this->value == self::JAVA_SCRIPT_MAP_REDUCE;
    }

    public static function javaScriptMapReduce(): IndexType
    {
        return new IndexType(self::JAVA_SCRIPT_MAP_REDUCE);
    }
}
