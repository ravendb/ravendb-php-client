<?php

namespace RavenDB\Documents\Indexes;


use RavenDB\Type\ValueObjectInterface;

class GroupByArrayBehavior implements ValueObjectInterface
{
    private const NOT_APPLICABLE = 'NotApplicable';
    private const BY_CONTENT = 'ByContent';
    private const BY_INDIVIDUAL_VALUES = 'ByIndividualValues';

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

    public function isNotApplicable(): bool
    {
        return $this->value == self::NOT_APPLICABLE;
    }

    public static function notApplicable(): GroupByArrayBehavior
    {
        return new GroupByArrayBehavior(self::NOT_APPLICABLE);
    }

    public function isByContent(): bool
    {
        return $this->value == self::BY_CONTENT;
    }

    public static function byContent(): GroupByArrayBehavior
    {
        return new GroupByArrayBehavior(self::BY_CONTENT);
    }

    public function isByIndividualValues(): bool
    {
        return $this->value == self::BY_INDIVIDUAL_VALUES;
    }

    public static function byIndividualValues(): GroupByArrayBehavior
    {
        return new GroupByArrayBehavior(self::BY_INDIVIDUAL_VALUES);
    }
}
