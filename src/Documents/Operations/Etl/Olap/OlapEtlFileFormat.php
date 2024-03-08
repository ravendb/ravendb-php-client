<?php

namespace RavenDB\Documents\Operations\Etl\Olap;

use RavenDB\Type\ValueObjectInterface;

class OlapEtlFileFormat implements ValueObjectInterface
{
    private const PARQUET = 'Parquet';

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

    public function isParquet(): bool
    {
        return $this->value == self::PARQUET;
    }

    public static function parquet(): OlapEtlFileFormat
    {
        return new OlapEtlFileFormat(self::PARQUET);
    }
}
