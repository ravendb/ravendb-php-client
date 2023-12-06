<?php

namespace RavenDB\Exceptions;

class ConcurrencyViolation
{
    private ?ViolationOnType $type = null;
    private ?string $id = null;
    private ?int $expected = null;
    private ?int $actual = null;

    public function getType(): ?ViolationOnType
    {
        return $this->type;
    }

    public function setType(?ViolationOnType $type): void
    {
        $this->type = $type;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getExpected(): ?int
    {
        return $this->expected;
    }

    public function setExpected(?int $expected): void
    {
        $this->expected = $expected;
    }

    public function getActual(): ?int
    {
        return $this->actual;
    }

    public function setActual(?int $actual): void
    {
        $this->actual = $actual;
    }
}
