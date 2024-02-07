<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\ValueObjectInterface;

class LuceneIndexInputType implements ValueObjectInterface
{
    private const STANDARD = 'Standard';
    private const BUFFERED = 'Buffered';

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

    public function isStandard(): bool
    {
        return $this->value == self::STANDARD;
    }

    public static function standard(): LuceneIndexInputType
    {
        return new LuceneIndexInputType(self::STANDARD);
    }

    public function isBuffered(): bool
    {
        return $this->value == self::BUFFERED;
    }

    public static function buffered(): LuceneIndexInputType
    {
        return new LuceneIndexInputType(self::BUFFERED);
    }
}
