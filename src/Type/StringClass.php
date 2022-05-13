<?php

namespace RavenDB\Type;

class StringClass implements ValueObjectInterface
{

    private ?string $value = null;

    public function __construct(?string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->getValue() ?? '';
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }
}
