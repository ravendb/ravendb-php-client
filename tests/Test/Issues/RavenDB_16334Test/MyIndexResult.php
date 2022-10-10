<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16334Test;

class MyIndexResult
{
    private ?string $name = null;
    /** @var mixed  */
    private $value = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = floatval($value);
    }
}
